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

        return view('user.attendance_detail', $this->buildShowViewData($attendance, $latestRequest));
    }

    public function store(AttendanceCorrectionRequestStoreRequest $request, $id)
    {
        $user = Auth::user();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $latestRequest = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($latestRequest && $latestRequest->status === 'pending') {
            return redirect()->route('attendance.detail.show', ['id' => $attendance->id]);
        }

        $date = $attendance->date;
        $workStartAt = $date->copy()->setTimeFromTimeString($request->input('work_start_at'));
        $workEndAt = $date->copy()->setTimeFromTimeString($request->input('work_end_at'));

        DB::transaction(function () use ($request, $attendance, $user, $date, $workStartAt, $workEndAt) {
            $correctionRequest = AttendanceCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'approved_by' => null,
                'date' => $date->toDateString(),
                'work_start_at' => $workStartAt,
                'work_end_at' => $workEndAt,
                'memo' => $request->input('memo'),
                'status' => 'pending',
            ]);

            foreach ($request->input('breaks', []) as $break) {
                $start = $break['start'] ?? null;
                $end = $break['end'] ?? null;

                if (! $start || ! $end) {
                    continue;
                }

                AttendanceCorrectionRequestBreak::create([
                    'request_id' => $correctionRequest->id,
                    'break_start_at' => $date->copy()->setTimeFromTimeString($start),
                    'break_end_at' => $date->copy()->setTimeFromTimeString($end),
                ]);
            }
        });

        return redirect()->route('attendance.detail.show', ['id' => $attendance->id]);
    }

    private function buildShowViewData(Attendance $attendance, ?AttendanceCorrectionRequest $latestRequest): array
    {
        $isPending = $latestRequest && $latestRequest->status === 'pending';
        $displaySource = $isPending ? $latestRequest : $attendance;

        $breakRows = $this->formatBreakRows(
            $isPending ? $latestRequest->breaks : $attendance->breaks,
            ! $isPending
        );

        return [
            'attendance' => $attendance,
            'isPending' => $isPending,
            'latestRequest' => $latestRequest,
            'breakRows' => $breakRows,
            'yearLabel' => $attendance->date->format('Y年'),
            'mdLabel' => $attendance->date->format('n月j日'),
            'displayWorkStart' => $displaySource->work_start_at?->format('H:i'),
            'displayWorkEnd' => $displaySource->work_end_at?->format('H:i'),
            'displayMemo' => $displaySource->memo ?? '',
        ];
    }

    private function formatBreakRows($breaks, bool $appendEmptyRow = false): array
    {
        $rows = $breaks
            ->sortBy('id')
            ->map(fn($break) => [
                'start' => $break->break_start_at?->format('H:i'),
                'end' => $break->break_end_at?->format('H:i'),
            ])
            ->values()
            ->all();

        if ($appendEmptyRow) {
            $rows[] = ['start' => null, 'end' => null];
        }

        return $rows;
    }
}