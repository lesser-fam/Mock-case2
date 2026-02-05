<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
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

            $attendance->update([
                'work_end_at' => $now,
                'status' => 'finished',
            ]);
        });

        return redirect()->route('attendance');
    }
}
