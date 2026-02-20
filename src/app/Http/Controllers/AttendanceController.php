<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequestStoreRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\BreakTime;
use App\Services\AttendanceMonthTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $status = $attendance?->status ?? 'outside';

        return view('user.attendance', [
            'attendance' => $attendance,
            'status' => $status,
            'dateLabel' => Carbon::now()->isoFormat('YYYY年M月D日(ddd)'),
            'timeLabel' => Carbon::now()->format('H:i'),
        ]);
    }

    public function workStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()->firstOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                ['status' => 'outside']
            );

            if ($attendance->status !== 'outside') {
                abort(409);
            }

            $attendance->update([
                'work_start_at' => $now,
                'status' => 'working',
            ]);
        });

        return redirect()->route('attendance');
    }

    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance->status !== 'working') {
                abort(409);
            }

            $hasOpenBreak = BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->lockForUpdate()
                ->exists();

            if ($hasOpenBreak) {
                abort(409);
            }

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start_at' => $now,
            ]);

            $attendance->update(['status' => 'breaking']);
        });

        return redirect()->route('attendance');
    }

    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance->status !== 'breaking') {
                abort(409);
            }

            $latestBreak = BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            $latestBreak->update(['break_end_at' => $now]);
            $attendance->update(['status' => 'working']);
        });

        return redirect()->route('attendance');
    }

    public function workEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance->status !== 'working') {
                abort(409);
            }

            $openBreak = BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($openBreak) {
                $openBreak->update(['break_end_at' => $now]);
            }

            $attendance->update([
                'work_end_at' => $now,
                'status' => 'finished',
            ]);
        });

        return redirect()->route('attendance');
    }

    public function list(Request $request, AttendanceMonthTable $table)
    {
        $user = Auth::user();

        $monthStr = $request->query('month');
        $base = null;

        if (is_string($monthStr) && preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            try {
                $base = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
            } catch (\Throwable $e) {
                $base = null;
            }
        }

        $base = $base ?: now()->startOfMonth();

        return view('user.attendance_list', $table->build($user->id, $base));
    }

    public function detail($id)
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
            return redirect()->route('attendance.detail', ['id' => $attendance->id]);
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

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }
}
