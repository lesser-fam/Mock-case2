<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAttendanceUpdater
{
    public function updateAttendance(Attendance $attendance, string $workStart, string $workEnd, ?string $memo, array $breaksInput): void
    {
        $pending = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            abort(409, '承認待ちのため修正はできません');
        }

        $date = $attendance->date instanceof Carbon
            ? $attendance->date->copy()
            : Carbon::parse($attendance->date);

        $workStartAt = $date->copy()->setTimeFromTimeString($workStart);
        $workEndAt   = $date->copy()->setTimeFromTimeString($workEnd);

        $breakRows = [];
        foreach ($breaksInput as $b) {
            $bs = $b['start'] ?? null;
            $be = $b['end'] ?? null;
            if (!$bs || !$be) continue;

            $breakRows[] = [
                'start' => $date->copy()->setTimeFromTimeString($bs),
                'end'   => $date->copy()->setTimeFromTimeString($be),
            ];
        }

        DB::transaction(function () use ($attendance, $workStartAt, $workEndAt, $memo, $breakRows) {
            $attendance->update([
                'work_start_at' => $workStartAt,
                'work_end_at'   => $workEndAt,
                'memo'          => $memo,
                'status'        => 'finished',
            ]);

            BreakTime::query()->where('attendance_id', $attendance->id)->delete();

            foreach ($breakRows as $b) {
                BreakTime::create([
                    'attendance_id'   => $attendance->id,
                    'break_start_at'  => $b['start'],
                    'break_end_at'    => $b['end'],
                ]);
            }
        });
    }
}
