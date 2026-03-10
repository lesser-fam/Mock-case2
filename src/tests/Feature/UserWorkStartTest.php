<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWorkStartTest extends TestCase
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
    public function 出勤ボタンが正しく機能する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $me = $this->user();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertStatus(200);
        $res->assertSee('出勤', false);

        $res = $this->actingAs($me)->post(route('attendance.stamp.work_start'));
        $res->assertRedirect(route('attendance.stamp.show'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $me->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'working',
        ]);

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertSee('出勤中', false);
    }

    /**
     * @test
     */
    public function 出勤は一日一回のみできる(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 18, 0, 0, 'Asia/Tokyo'));

        $me = $this->user();

        Attendance::factory()
            ->for($me, 'user')
            ->forDate(Carbon::today()->toDateString())
            ->finished('09:00', '18:00')
            ->create();

        $res = $this->actingAs($me)->get(route('attendance.stamp.show'));
        $res->assertStatus(200);
        $res->assertDontSee('attendance/work/start', false);
        $res->assertSee('お疲れ様でした。', false);
    }

    /**
     * @test
     */
    public function 出勤時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 5, 0, 'Asia/Tokyo'));

        $me = $this->user();

        $this->actingAs($me)->post(route('attendance.stamp.work_start'));

        $res = $this->actingAs($me)->get(route('attendance.month.index', [
            'month' => Carbon::now()->format('Y-m'),
        ]));

        $res->assertStatus(200);
        $res->assertSee('03/03(火)', false);
        $res->assertSee('09:05', false);
    }
}
