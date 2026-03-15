<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWorkEndTest extends TestCase
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
    public function 退勤ボタンが正しく機能する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 18, 10, 0, 'Asia/Tokyo'));

        $me = $this->user();

        $attendance = Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->working('09:00')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('退勤', false);

        $res = $this->actingAs($me)->post(route('attendance.stamp.work_end'));
        $res->assertRedirect(route('attendance.stamp.show'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'finished',
        ]);

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('退勤済', false);
        $res->assertSee('お疲れ様でした。', false);
    }

    /**
     * @test
     */
    public function 退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $me = $this->user();

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 5, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.work_start'));

        Carbon::setTestNow(Carbon::create(2026, 3, 3, 18, 10, 0, 'Asia/Tokyo'));
        $this->actingAs($me)->post(route('attendance.stamp.work_end'));

        $res = $this->actingAs($me)->get(route('attendance.month.index', [
            'month' => Carbon::now()->format('Y-m'),
        ]));
        $res->assertStatus(200);
        $res->assertSee('03/03(火)', false);
        $res->assertSee('18:10', false);
    }
}
