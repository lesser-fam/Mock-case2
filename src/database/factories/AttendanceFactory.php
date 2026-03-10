<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = Carbon::today()
            ->subDays($this->faker->numberBetween(1, 500))
            ->toDateString();

        return [
            'user_id' => User::factory(),
            'date' => $date,
            'work_start_at' => null,
            'work_end_at' => null,
            'status' => 'outside',
            'memo' => null,
        ];
    }

    public function forDate(string $date): static
    {
        return $this->state(fn() => [
            'date' => $date,
        ]);
    }

    public function outside(): static
    {
        return $this->state(fn() => [
            'work_start_at' => null,
            'work_end_at' => null,
            'status' => 'outside',
        ]);
    }

    public function working(string $start = '09:00'): static
    {
        return $this->state(function (array $attrs) use ($start) {
            $date = Carbon::parse($attrs['date']);

            return [
                'work_start_at' => $date->copy()->setTimeFromTimeString($start),
                'work_end_at' => null,
                'status' => 'working',
            ];
        });
    }

    public function breaking(string $start = '09:00'): static
    {
        return $this->state(function (array $attrs) use ($start) {
            $date = Carbon::parse($attrs['date']);

            return [
                'work_start_at' => $date->copy()->setTimeFromTimeString($start),
                'work_end_at' => null,
                'status' => 'breaking',
            ];
        });
    }

    public function finished(string $start = '09:00', string $end = '18:00'): static
    {
        return $this->state(function (array $attrs) use ($start, $end) {
            $date = Carbon::parse($attrs['date']);

            return [
                'work_start_at' => $date->copy()->setTimeFromTimeString($start),
                'work_end_at' => $date->copy()->setTimeFromTimeString($end),
                'status' => 'finished',
            ];
        });
    }

    public function withMemo(?string $memo): static
    {
        return $this->state(fn() => [
            'memo' => $memo,
        ]);
    }
}
