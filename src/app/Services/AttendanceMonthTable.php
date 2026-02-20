<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceMonthTable
{
    /**
     * @return array{
     *   baseMonth: Carbon,
     *   prevMonth: string,
     *   nextMonth: string,
     *   days: array<int, array{
     *     date: Carbon,
     *     attendance: ?Attendance,
     *     breakMinutes: int,
     *     workMinutes: ?int
     *   }>
     * }
     */
    public function build(int $userId, Carbon $month): array
    {
        $baseMonth = $month->copy()->startOfMonth();
        $from = $baseMonth->copy()->startOfMonth();
        $to   = $baseMonth->copy()->endOfMonth();

        // ここが重要：存在しない日付の Attendance を outside で作る
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

            $breakMinutes = 0;
            if ($a) {
                $breakMinutes = $a->breaks->sum(function ($b) {
                    if (!$b->break_start_at || !$b->break_end_at) return 0;
                    return $b->break_start_at->diffInMinutes($b->break_end_at);
                });
            }

            $workMinutes = null;
            if ($a && $a->work_start_at && $a->work_end_at) {
                $workMinutes = $a->work_start_at->diffInMinutes($a->work_end_at) - $breakMinutes;
                if ($workMinutes < 0) $workMinutes = 0;
            }

            $days[] = [
                'date' => $d->copy(),
                'attendance' => $a,
                'breakMinutes' => (int) $breakMinutes,
                'workMinutes' => $workMinutes,
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
