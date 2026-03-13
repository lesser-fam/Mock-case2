<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class UserAttendanceStampController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $status = $attendance?->status ?? 'outside';

        $statusLabel = match ($status) {
            'outside' => '勤務外',
            'working' => '出勤中',
            'breaking' => '休憩中',
            'finished' => '退勤済',
            default => '勤務外',
        };

        return view('user.attendance', [
            'attendance' => $attendance,
            'status' => $status,
            'statusLabel' => $statusLabel,
            'dateLabel' => $now->isoFormat('YYYY年M月D日(ddd)'),
            'timeLabel' => $now->format('H:i'),
        ]);
    }

    public function workStart()
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

        return redirect()->route('attendance.stamp.show');
    }

    public function breakStart()
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

        return redirect()->route('attendance.stamp.show');
    }

    public function breakEnd()
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

        return redirect()->route('attendance.stamp.show');
    }

    public function workEnd()
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

        return redirect()->route('attendance.stamp.show');
    }
}
