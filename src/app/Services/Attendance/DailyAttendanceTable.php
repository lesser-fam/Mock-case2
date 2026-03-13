<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\User;
use App\Services\WorkTimeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyAttendanceTable
{
    public function __construct(private WorkTimeCalculator $calc) {}

    public function build(Carbon $baseDate): array
    {
        $staffs = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        $staffIds = $staffs->pluck('id')->all();
        $dateKey = $baseDate->toDateString();

        $existing = Attendance::query()
            ->whereDate('date', $dateKey)
            ->whereIn('user_id', $staffIds)
            ->get()
            ->keyBy('user_id');

        DB::transaction(function () use ($staffs, $existing, $dateKey) {
            foreach ($staffs as $staff) {
                if ($existing->has($staff->id)) continue;

                Attendance::create([
                    'user_id' => $staff->id,
                    'date' => $dateKey,
                    'status' => 'outside',
                    'work_start_at' => null,
                    'work_end_at' => null,
                    'memo' => null,
                ]);
            }
        });

        $attendances = Attendance::query()
            ->whereDate('date', $dateKey)
            ->whereIn('user_id', $staffIds)
            ->with(['breaks', 'user'])
            ->get()
            ->keyBy('user_id');

        $rows = [];
        foreach ($staffs as $staff) {
            /** @var Attendance|null $a */
            $a = $attendances->get($staff->id);

            $start = $a?->work_start_at ? $a->work_start_at->format('H:i') : '';
            $end   = $a?->work_end_at ? $a->work_end_at->format('H:i') : '';

            $hasWork = $a !== null && $a->work_start_at !== null && $a->work_end_at !== null;

            $breakMin = ($a && $a->relationLoaded('breaks'))
                ? $this->calc->breakMinutes($a->breaks)
                : 0;

            $workMin = $a
                ? $this->calc->workMinutes($a->work_start_at, $a->work_end_at, $breakMin)
                : null;

            $rows[] = [
                'staff' => $staff,
                'attendance' => $a,
                'start' => $start,
                'end' => $end,
                'breakLabel' => $hasWork ? $this->calc->hmLabel($breakMin) : '',
                'workLabel' => $this->calc->hmLabel($workMin),
            ];
        }

        return [
            'baseDate' => $baseDate,
            'prevDate' => $baseDate->copy()->subDay()->toDateString(),
            'nextDate' => $baseDate->copy()->addDay()->toDateString(),
            'rows' => $rows,
        ];
    }
}
