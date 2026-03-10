<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Tokyo']);
        Carbon::setLocale('ja');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(array $overrides = []): User
    {
        $factory = User::factory()->user()->named('山田 太郎');

        if (isset($overrides['name'])) {
            $factory = User::factory()->user()->named($overrides['name']);
            unset($overrides['name']);
        }

        if (isset($overrides['email'])) {
            $factory = $factory->withEmail($overrides['email']);
            unset($overrides['email']);
        }

        return $factory->create($overrides);
    }

    private function attendanceFor(User $user, array $overrides = []): Attendance
    {
        $factory = Attendance::factory()
            ->for($user, 'user')
            ->forDate('2026-03-03')
            ->finished('09:00', '18:00');

        return $factory->create($overrides);
    }

    /**
     * @test
     */
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->attendanceFor($user);

        $res = $this->actingAs($user)->get(route('attendance.detail.show', ['id' => $attendance->id]));
        $res->assertStatus(200);
        $res->assertSee('山田 太郎', false);
    }

    /**
     * @test
     */
    public function 勤怠詳細画面の日付が選択した日付になっている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->attendanceFor($user);

        $res = $this->actingAs($user)->get(route('attendance.detail.show', ['id' => $attendance->id]));
        $res->assertStatus(200);
        $res->assertSee('2026年', false);
        $res->assertSee('3月3日', false);
    }

    /**
     * @test
     */
    public function 出勤退勤に記されている時間がログインユーザーの打刻と一致している(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->attendanceFor($user);

        $res = $this->actingAs($user)->get(route('attendance.detail.show', ['id' => $attendance->id]));
        $res->assertStatus(200);
        $res->assertSee('name="work_start_at"', false);
        $res->assertSee('value="09:00"', false);
        $res->assertSee('name="work_end_at"', false);
        $res->assertSee('value="18:00"', false);
    }

    /**
     * @test
     */
    public function 休憩に記されている時間がログインユーザーの打刻と一致している(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->attendanceFor($user);

        BreakTime::factory()
            ->forAttendance($attendance)
            ->timeRange('12:30', '13:00')
            ->create();

        $res = $this->actingAs($user)->get(route('attendance.detail.show', ['id' => $attendance->id]));
        $res->assertStatus(200);
        $res->assertSee('name="breaks[0][start]"', false);
        $res->assertSee('value="12:30"', false);
        $res->assertSee('name="breaks[0][end]"', false);
        $res->assertSee('value="13:00"', false);
    }
}
