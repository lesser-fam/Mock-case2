<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceStatusTest extends TestCase
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

    private function user(): User
    {
        return User::factory()->user()->create();
    }

    /** @test */
    public function 勤務外の場合_勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 5, 0, 'Asia/Tokyo'));

        $me = $this->user();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));

        $res->assertStatus(200);
        $res->assertSee('勤務外', false);
    }

    /** @test */
    public function 出勤中の場合_勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 5, 0, 'Asia/Tokyo'));

        $me = $this->user();

        Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->working('09:05')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));

        $res->assertStatus(200);
        $res->assertSee('出勤中', false);
    }

    /** @test */
    public function 休憩中の場合_勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 12, 0, 0, 'Asia/Tokyo'));

        $me = $this->user();

        Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->breaking('09:00')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));

        $res->assertStatus(200);
        $res->assertSee('休憩中', false);
    }

    /** @test */
    public function 退勤済の場合_勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 18, 10, 0, 'Asia/Tokyo'));

        $me = $this->user();

        Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->finished('09:00', '18:10')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));

        $res->assertStatus(200);
        $res->assertSee('退勤済', false);
    }
}
