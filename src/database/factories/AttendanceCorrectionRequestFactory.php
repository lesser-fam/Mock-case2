<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AttendanceCorrectionRequest>
 */
class AttendanceCorrectionRequestFactory extends Factory
{
    protected $model = AttendanceCorrectionRequest::class;

    public function definition(): array
    {
        $date = Carbon::today();

        return [
            'user_id' => User::factory()->user(),
            'attendance_id' => Attendance::factory(),
            'approved_by' => null,
            'date' => $date->toDateString(),
            'work_start_at' => $date->copy()->setTime(9, 10),
            'work_end_at' => $date->copy()->setTime(18, 10),
            'memo' => '修正申請テスト用メモ',
            'status' => 'pending',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (AttendanceCorrectionRequest $req) {
            $attendance = $req->attendance()->first();
            $user = $req->applicant()->first();

            if (!$attendance || !$user) {
                return;
            }

            if ((int) $attendance->user_id !== (int) $user->id) {
                $attendance->update(['user_id' => $user->id]);
            }

            $date = $attendance->date instanceof Carbon
                ? $attendance->date->copy()
                : Carbon::parse($attendance->date);

            $workStartAt = $req->work_start_at
                ? Carbon::parse($req->work_start_at)
                : $date->copy()->setTime(9, 10);

            $workEndAt = $req->work_end_at
                ? Carbon::parse($req->work_end_at)
                : $date->copy()->setTime(18, 10);

            $req->update([
                'date' => $date->toDateString(),
                'work_start_at' => $date->copy()->setTimeFromTimeString($workStartAt->format('H:i:s')),
                'work_end_at' => $date->copy()->setTimeFromTimeString($workEndAt->format('H:i:s')),
            ]);
        });
    }

    public function forAttendanceAndUser(Attendance $attendance, User $user): static
    {
        $date = $attendance->date instanceof Carbon
            ? $attendance->date->copy()
            : Carbon::parse($attendance->date);

        return $this->state(fn() => [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'work_start_at' => $date->copy()->setTime(9, 10),
            'work_end_at' => $date->copy()->setTime(18, 10),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function approved(?User $admin = null): static
    {
        $admin = $admin ?: User::factory()->admin()->create();

        return $this->state(fn() => [
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }
}
