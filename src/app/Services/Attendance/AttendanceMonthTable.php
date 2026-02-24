<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Services\WorkTimeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceMonthTable
{
    public function __construct(private WorkTimeCalculator $calc) {}

    public function build(int $userId, Carbon $month): array
    {
        $baseMonth = $month->copy()->startOfMonth();
        $from = $baseMonth->copy()->startOfMonth();
        $to   = $baseMonth->copy()->endOfMonth();

        $this->ensureMonthlyAttendancesExist($userId, $from, $to);

        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy(fn($a) => $a->date->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            /** @var ?Attendance $a */
            $a = $attendances->get($key);

            $hasWork = $a && $a->work_start_at && $a->work_end_at;

            $breakMin = $a ? $this->calc->breakMinutes($a->breaks) : 0;
            $workMin  = $a ? $this->calc->workMinutes($a->work_start_at, $a->work_end_at, $breakMin) : null;

            $weekday = ['日', '月', '火', '水', '木', '金', '土'][$d->dayOfWeek];
            $dateLabel = $d->format('m/d') . "($weekday)";

            $start = $a?->work_start_at ? $a->work_start_at->format('H:i') : '';
            $end   = $a?->work_end_at   ? $a->work_end_at->format('H:i')   : '';

            $days[] = [
                'date' => $d->copy(),
                'attendance' => $a,
                'breakMinutes' => $breakMin,
                'workMinutes' => $workMin,
                'dateLabel' => $dateLabel,
                'start' => $start,
                'end' => $end,
                'breakLabel' => $hasWork ? $this->calc->hmLabel($breakMin) : '',
                'workLabel'  => $this->calc->hmLabel($workMin),
            ];
        }

        return [
            'baseMonth' => $baseMonth,
            'prevMonth' => $baseMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $baseMonth->copy()->addMonth()->format('Y-m'),
            'days' => $days,
        ];
    }

    private function ensureMonthlyAttendancesExist(int $userId, Carbon $from, Carbon $to): void
    {
        DB::transaction(function () use ($userId, $from, $to) {
            $existingDates = Attendance::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->all();

            $existingSet = array_flip($existingDates);

            $rows = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $dateStr = $d->toDateString();

                if (!isset($existingSet[$dateStr])) {
                    $rows[] = [
                        'user_id' => $userId,
                        'date' => $dateStr,
                        'status' => 'outside',
                        'work_start_at' => null,
                        'work_end_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($rows) {
                Attendance::query()->insert($rows);
            }
        });
    }
}
