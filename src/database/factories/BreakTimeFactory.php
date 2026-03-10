<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<BreakTime>
 */
class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'break_start_at' => now(),
            'break_end_at' => now()->addHour(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (BreakTime $break) {
            $attendance = $break->attendance()->first();

            if (!$attendance) {
                return;
            }

            $date = $attendance->date instanceof Carbon
                ? $attendance->date->copy()
                : Carbon::parse($attendance->date);

            $start = $break->break_start_at
                ? Carbon::parse($break->break_start_at)
                : $date->copy()->setTime(12, 0);

            $end = $break->break_end_at
                ? Carbon::parse($break->break_end_at)
                : null;

            $break->update([
                'break_start_at' => $date->copy()->setTimeFromTimeString($start->format('H:i:s')),
                'break_end_at' => $end
                    ? $date->copy()->setTimeFromTimeString($end->format('H:i:s'))
                    : null,
            ]);
        });
    }

    public function forAttendance(Attendance $attendance): static
    {
        return $this->state(fn() => [
            'attendance_id' => $attendance->id,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn() => [
            'break_end_at' => null,
        ]);
    }

    public function timeRange(string $start, ?string $end): static
    {
        return $this->state(fn() => [
            'break_start_at' => Carbon::parse("2000-01-01 {$start}:00"),
            'break_end_at' => $end ? Carbon::parse("2000-01-01 {$end}:00") : null,
        ]);
    }
}
