<?php

namespace App\Services;

use Illuminate\Support\Collection;

class WorkTimeCalculator
{
    /**
     * @param  Collection<int, object>  $breaks  break_start_at / break_end_at を持つ想定
     */
    public function breakMinutes(Collection $breaks): int
    {
        return (int) $breaks->sum(function ($b) {
            if (empty($b->break_start_at) || empty($b->break_end_at)) return 0;
            return $b->break_start_at->diffInMinutes($b->break_end_at);
        });
    }

    /**
     * @return int|null  出勤退勤が揃っていないなら null
     */
    public function workMinutes($workStartAt, $workEndAt, int $breakMinutes): ?int
    {
        if (!$workStartAt || !$workEndAt) return null;

        $min = $workStartAt->diffInMinutes($workEndAt) - $breakMinutes;
        if ($min < 0) $min = 0;

        return (int) $min;
    }

    public function hmLabel(?int $minutes): string
    {
        if ($minutes === null) return '';
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
