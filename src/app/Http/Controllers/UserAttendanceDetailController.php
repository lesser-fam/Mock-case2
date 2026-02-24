<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequestStoreRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserAttendanceDetailController extends Controller
{
    public function show($id)
    {
        $user = Auth::user();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->with(['breaks', 'user'])
            ->firstOrFail();

        $latestRequest = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->with('breaks')
            ->latest('id')
            ->first();

        $isPending = $latestRequest && $latestRequest->status === 'pending';

        $sourceWorkStart = $isPending ? $latestRequest?->work_start_at : $attendance->work_start_at;
        $sourceWorkEnd = $isPending ? $latestRequest?->work_end_at : $attendance->work_end_at;

        if ($isPending) {
            $breakRows = ($latestRequest?->breaks ?? collect())
                ->sortBy('id')
                ->map(fn($b) => [
                    'start' => $b->break_start_at?->format('H:i'),
                    'end'   => $b->break_end_at?->format('H:i'),
                ])->values()->all();
        } else {
            $breakRows = $attendance->breaks
                ->sortBy('id')
                ->map(fn($b) => [
                    'start' => $b->break_start_at?->format('H:i'),
                    'end'   => $b->break_end_at?->format('H:i'),
                ])->values()->all();

            $breakRows[] = ['start' => null, 'end' => null];
        }

        $displayMemo = $isPending
            ? ($latestRequest?->memo ?? '')
            : ($attendance->memo ?? '');

        $date = $attendance->date;
        $yearLabel = $date->format('Y年');
        $mdLabel = $date->format('n月j日');

        return view('user.attendance_detail', [
            'attendance' => $attendance,
            'isPending' => $isPending,
            'latestRequest' => $latestRequest,
            'breakRows' => $breakRows,
            'yearLabel' => $yearLabel,
            'mdLabel' => $mdLabel,
            'displayWorkStart' => $sourceWorkStart?->format('H:i'),
            'displayWorkEnd' => $sourceWorkEnd?->format('H:i'),
            'displayMemo' => $displayMemo,
        ]);
    }

    public function request(AttendanceCorrectionRequestStoreRequest $request, $id)
    {
        $user = Auth::user();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // すでに pending があるなら二重申請禁止
        $latestRequest = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($latestRequest && $latestRequest->status === 'pending') {
            return redirect()->route('attendance.detail.show', ['id' => $attendance->id]);
        }

        $date = $attendance->date;

        $workStart = $request->input('work_start_at');
        $workEnd   = $request->input('work_end_at');

        $workStartAt = $date->copy()->setTimeFromTimeString($workStart);
        $workEndAt   = $date->copy()->setTimeFromTimeString($workEnd);

        DB::transaction(function () use ($request, $attendance, $user, $date, $workStartAt, $workEndAt) {
            $req = AttendanceCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'       => $user->id,
                'approved_by'   => null,
                'date'          => $date->toDateString(),
                'work_start_at' => $workStartAt,
                'work_end_at'   => $workEndAt,
                'memo'          => $request->input('memo'),
                'status'        => 'pending',
            ]);

            $breaks = $request->input('breaks', []);
            foreach ($breaks as $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                if (!$bs || !$be) {
                    continue;
                }

                AttendanceCorrectionRequestBreak::create([
                    'request_id'     => $req->id,
                    'break_start_at' => $date->copy()->setTimeFromTimeString($bs),
                    'break_end_at'   => $date->copy()->setTimeFromTimeString($be),
                ]);
            }
        });

        return redirect()->route('attendance.detail.show', ['id' => $attendance->id]);
    }
}
