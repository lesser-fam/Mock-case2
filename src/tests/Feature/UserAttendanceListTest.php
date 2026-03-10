<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceListTest extends TestCase
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

    private function user(array $overrides = []): User
    {
        $factory = User::factory()->user();

        if (isset($overrides['name'])) {
            $factory = $factory->named($overrides['name']);
            unset($overrides['name']);
        }

        if (isset($overrides['email'])) {
            $factory = $factory->withEmail($overrides['email']);
            unset($overrides['email']);
        }

        return $factory->create($overrides);
    }

    private function seedFinishedAttendanceWithBreak(
        User $user,
        string $date,
        string $start,
        string $end,
        string $breakStart,
        string $breakEnd
    ): Attendance {
        $attendance = Attendance::factory()
            ->for($user, 'user')
            ->forDate($date)
            ->finished($start, $end)
            ->create();

        BreakTime::factory()
            ->forAttendance($attendance)
            ->timeRange($breakStart, $breakEnd)
            ->create();

        return $attendance;
    }

    /**
     * @test
     */
    public function 自分が行った勤怠情報が全て表示されている_複数日の勤怠が一覧に出る(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();

        $this->seedFinishedAttendanceWithBreak($user, '2026-03-01', '09:00', '18:00', '12:00', '13:00');
        $this->seedFinishedAttendanceWithBreak($user, '2026-03-15', '10:00', '19:00', '13:00', '14:00');
        $this->seedFinishedAttendanceWithBreak($user, '2026-03-31', '08:30', '17:30', '12:00', '13:00');

        $res = $this->actingAs($user)->get(route('attendance.month.index', [
            'month' => '2026-03',
        ]));

        $res->assertStatus(200);

        $res->assertSee('03/01(日)', false);
        $res->assertSee('09:00', false);
        $res->assertSee('18:00', false);

        $res->assertSee('03/15(日)', false);
        $res->assertSee('10:00', false);
        $res->assertSee('19:00', false);
        
        $res->assertSee('03/31(火)', false);
        $res->assertSee('08:30', false);
        $res->assertSee('17:30', false);
        
        $res->assertSee('1:00', false);
        $res->assertSee('8:00', false);
    }

    /**
     * @test
     */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();

        $res = $this->actingAs($user)->get(route('attendance.month.index'));

        $res->assertStatus(200);
        $res->assertSee('2026/03', false);
        $res->assertSee('value="2026-03"', false);
    }

    /**
     * @test
     */
    public function 前月を押下した時に前月の情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();

        $this->seedFinishedAttendanceWithBreak($user, '2026-02-05', '09:00', '18:00', '12:00', '13:00');
        $this->seedFinishedAttendanceWithBreak($user, '2026-02-20', '10:00', '19:00', '13:00', '14:00');

        $this->seedFinishedAttendanceWithBreak($user, '2026-03-03', '09:05', '18:10', '12:00', '13:00');

        $res = $this->actingAs($user)->get(route('attendance.month.index', [
            'month' => '2026-02',
        ]));

        $res->assertStatus(200);

        $res->assertSee('2026/02', false);
        $res->assertSee('value="2026-02"', false);

        $res->assertSee('02/05(木)', false);
        $res->assertSee('02/20(金)', false);

        $res->assertDontSee('03/03(火)', false);
        $res->assertDontSee('09:05', false);
        $res->assertDontSee('18:10', false);
    }

    /**
     * @test
     */
    public function 翌月を押下した時に翌月の情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();

        $this->seedFinishedAttendanceWithBreak($user, '2026-04-02', '09:00', '18:00', '12:00', '13:00');
        $this->seedFinishedAttendanceWithBreak($user, '2026-04-28', '10:00', '19:00', '13:00', '14:00');

        $this->seedFinishedAttendanceWithBreak($user, '2026-03-03', '09:05', '18:10', '12:00', '13:00');

        $res = $this->actingAs($user)->get(route('attendance.month.index', [
            'month' => '2026-04',
        ]));

        $res->assertStatus(200);

        $res->assertSee('2026/04', false);
        $res->assertSee('value="2026-04"', false);

        $res->assertSee('04/02(木)', false);
        $res->assertSee('04/28(火)', false);

        $res->assertDontSee('03/03(火)', false);
        $res->assertDontSee('09:05', false);
        $res->assertDontSee('18:10', false);
    }

    /**
     * @test
     */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();

        $attendance = $this->seedFinishedAttendanceWithBreak($user, '2026-03-03', '09:05', '18:10', '12:00', '13:00');

        $res = $this->actingAs($user)->get(route('attendance.month.index', [
            'month' => '2026-03',
        ]));

        $res->assertStatus(200);

        $detailUrl = route('attendance.detail.show', ['id' => $attendance->id]);
        $res->assertSee('詳細', false);
        $res->assertSee('href="' . $detailUrl . '"', false);

        $this->actingAs($user)->get($detailUrl)->assertStatus(200);
    }
}
