<?php

namespace App\Services;

use Carbon\Carbon;

class DateQueryParser
{
    public function parseDate(?string $dateStr, ?Carbon $fallback = null): Carbon
    {
        $fallback = $fallback ?: now();

        try {
            return $dateStr
                ? Carbon::createFromFormat('Y-m-d', $dateStr)->startOfDay()
                : $fallback->copy()->startOfDay();
        } catch (\Throwable $e) {
            return $fallback->copy()->startOfDay();
        }
    }

    public function parseMonth(?string $monthStr, ?Carbon $fallback = null): Carbon
    {
        $fallback = $fallback ?: now();

        if (!is_string($monthStr) || !preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            return $fallback->copy()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        } catch (\Throwable $e) {
            return $fallback->copy()->startOfMonth();
        }
    }
}
