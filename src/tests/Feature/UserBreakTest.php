<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        config(['app.timezone' => 'Asia/Tokyo']);
        Carbon::setLocale('ja');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(): User
    {
        return User::factory()->user()->create();
    }

    /**
     * @test
     */
    public function 休憩ボタンが正しく機能する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 12, 0, 0, 'Asia/Tokyo'));

        $me = $this->user();

        $attendance = Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->working('09:00')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('休憩入', false);

        $res = $this->actingAs($me)->post(route('attendance.stamp.break_start'));
        $res->assertRedirect(route('attendance.stamp.show'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'breaking',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_end_at' => null,
        ]);

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('休憩中', false);
    }

    /**
     * @test
     */
    public function 休憩は一日に何回でもできる_2回目の休憩入ボタン表示(): void
    {
        $me = $this->user();

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));
        Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->working('09:00')
            ->create();

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 12, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 13, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.break_end'));

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertStatus(200);
        $res->assertSee('出勤中', false);
        $res->assertSee('休憩入', false);
    }

    /**
     * @test
     */
    public function 休憩戻ボタンが正しく機能する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 13, 0, 0, 'Asia/Tokyo'));

        $me = $this->user();

        $attendance = Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->breaking('09:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($attendance)
            ->open()
            ->timeRange('12:00', null)
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('休憩戻', false);

        $res = $this->actingAs($me)->post(route('attendance.stamp.break_end'));
        $res->assertRedirect(route('attendance.stamp.show'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'working',
        ]);

        $this->assertDatabaseMissing('breaks', [
            'attendance_id' => $attendance->id,
            'break_end_at' => null,
        ]);

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('出勤中', false);
    }

    /**
     * @test
     */
    public function 休憩戻は一日に何回でもできる_2回目の休憩戻ボタン表示(): void
    {
        $me = $this->user();

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));
        Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->working('09:00')
            ->create();

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 12, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 13, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.break_end'));

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 15, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.break_start'));

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertStatus(200);
        $res->assertSee('休憩中', false);
        $res->assertSee('休憩戻', false);
    }

    /**
     * @test
     */
    public function 休憩時刻が勤怠一覧画面で確認できる(): void
    {
        $me = $this->user();

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $attendance = Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->finished('09:00', '18:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($attendance)
            ->timeRange('12:00', '13:00')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.month.index', [
            'month' => '2026-03'
        ]));

        $res->assertStatus(200);
        $res->assertSee('03/03(火)', false);
        $res->assertSee('1:00', false);

        Carbon::setTestNow();
    }
}
