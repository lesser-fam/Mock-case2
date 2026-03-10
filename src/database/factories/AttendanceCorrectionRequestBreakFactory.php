<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AttendanceCorrectionRequestBreak>
 */
class AttendanceCorrectionRequestBreakFactory extends Factory
{
    protected $model = AttendanceCorrectionRequestBreak::class;

    public function definition(): array
    {
        return [
            'request_id' => AttendanceCorrectionRequest::factory(),
            'break_start_at' => now()->setTime(12, 0),
            'break_end_at' => now()->setTime(13, 0),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (AttendanceCorrectionRequestBreak $break) {
            $request = $break->request()->first();

            if (!$request) {
                return;
            }

            $date = Carbon::parse($request->date);

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

    public function forRequest(AttendanceCorrectionRequest $request): static
    {
        $date = Carbon::parse($request->date);

        return $this->state(fn() => [
            'request_id' => $request->id,
            'break_start_at' => $date->copy()->setTime(12, 0),
            'break_end_at' => $date->copy()->setTime(13, 0),
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
