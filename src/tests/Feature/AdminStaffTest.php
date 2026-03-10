<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
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

    private function admin(array $overrides = []): User
    {
        $factory = User::factory()->admin()->named('管理者 太郎')->withEmail('admin@example.com');

        if (isset($overrides['name'])) {
            $factory = User::factory()->admin()->named($overrides['name']);
            unset($overrides['name']);
        }

        if (isset($overrides['email'])) {
            $factory = $factory->withEmail($overrides['email']);
            unset($overrides['email']);
        }

        return $factory->create($overrides);
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

    private function finishedAttendance(
        User $user,
        string $date,
        string $start = '09:00',
        string $end = '18:00',
        ?string $memo = null
    ): Attendance {
        return Attendance::factory()
            ->for($user, 'user')
            ->forDate($date)
            ->finished($start, $end)
            ->create([
                'memo' => $memo,
            ]);
    }

    private function addBreak(Attendance $attendance, string $start, string $end): void
    {
        BreakTime::factory()
            ->forAttendance($attendance)
            ->timeRange($start, $end)
            ->create();
    }

    /**
     * @test
     */
    public function 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $admin = $this->admin();

        $user1 = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $user2 = $this->user([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
        ]);

        $res = $this->actingAs($admin)->get(route('admin.staff.index'));

        $res->assertStatus(200);

        $res->assertSee('山田 太郎', false);
        $res->assertSee('yamada@example.com', false);
        $res->assertSee('佐藤 花子', false);
        $res->assertSee('sato@example.com', false);

        $res->assertDontSee('admin@example.com', false);
        $res->assertDontSee('管理者 太郎', false);

        $res->assertSee(route('admin.staff.month.index', ['id' => $user1->id]), false);
        $res->assertSee(route('admin.staff.month.index', ['id' => $user2->id]), false);
    }

    /**
     * @test
     */
    public function ユーザーの勤怠情報が正しく表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $targetUser = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $otherUser = $this->user([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
        ]);

        $attendance1 = $this->finishedAttendance($targetUser, '2026-03-01', '09:00', '18:00');
        $this->addBreak($attendance1, '12:00', '13:00');

        $attendance2 = $this->finishedAttendance($targetUser, '2026-03-15', '10:00', '19:00');
        $this->addBreak($attendance2, '13:00', '13:30');

        $otherAttendance = $this->finishedAttendance($otherUser, '2026-03-15', '08:00', '17:00');
        $this->addBreak($otherAttendance, '12:00', '13:00');

        $res = $this->actingAs($admin)->get(route('admin.staff.month.index', [
            'id' => $targetUser->id,
            'month' => '2026-03',
        ]));

        $res->assertStatus(200);

        $res->assertSee('山田 太郎さんの勤怠', false);
        $res->assertSee('03/01(日)', false);
        $res->assertSee('09:00', false);
        $res->assertSee('18:00', false);
        $res->assertSee('1:00', false);
        $res->assertSee('8:00', false);

        $res->assertSee('03/15(日)', false);
        $res->assertSee('10:00', false);
        $res->assertSee('19:00', false);
        $res->assertSee('0:30', false);
        $res->assertSee('8:30', false);

        $res->assertDontSee('佐藤 花子さんの勤怠', false);
        $res->assertDontSee('08:00', false);

        $res->assertSee(route('admin.attendance.show', ['id' => $attendance1->id]), false);
        $res->assertSee(route('admin.attendance.show', ['id' => $attendance2->id]), false);
    }

    /**
     * @test
     */
    public function 前月を押下した時に表示月の前月の情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $targetUser = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $feb1 = $this->finishedAttendance($targetUser, '2026-02-03', '09:00', '18:00');
        $this->addBreak($feb1, '12:00', '13:00');

        $feb2 = $this->finishedAttendance($targetUser, '2026-02-20', '10:00', '19:00');
        $this->addBreak($feb2, '13:00', '13:30');

        $mar1 = $this->finishedAttendance($targetUser, '2026-03-05', '08:30', '17:30');
        $this->addBreak($mar1, '12:00', '13:00');

        $res = $this->actingAs($admin)->get(route('admin.staff.month.index', [
            'id' => $targetUser->id,
            'month' => '2026-02',
        ]));

        $res->assertStatus(200);

        $res->assertSee('2026/02', false);

        $res->assertSee('02/03(火)', false);
        $res->assertSee('02/20(金)', false);

        $res->assertDontSee('03/05(木)', false);
        $res->assertDontSee('2026/03', false);
    }

    /**
     * @test
     */
    public function 翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $targetUser = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $mar1 = $this->finishedAttendance($targetUser, '2026-03-05', '09:00', '18:00');
        $this->addBreak($mar1, '12:00', '13:00');

        $apr1 = $this->finishedAttendance($targetUser, '2026-04-01', '10:00', '19:00');
        $this->addBreak($apr1, '13:00', '13:30');

        $apr2 = $this->finishedAttendance($targetUser, '2026-04-18', '08:30', '17:30');
        $this->addBreak($apr2, '12:00', '13:00');

        $res = $this->actingAs($admin)->get(route('admin.staff.month.index', [
            'id' => $targetUser->id,
            'month' => '2026-04',
        ]));

        $res->assertStatus(200);

        $res->assertSee('2026/04', false);

        $res->assertSee('04/01(水)', false);
        $res->assertSee('04/18(土)', false);

        $res->assertDontSee('03/05(木)', false);
        $res->assertDontSee('2026/03', false);
    }

    /**
     * @test
     */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $targetUser = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $attendance = $this->finishedAttendance($targetUser, '2026-03-15', '09:00', '18:00', '通常勤務');
        $this->addBreak($attendance, '12:00', '13:00');

        $list = $this->actingAs($admin)->get(route('admin.staff.month.index', [
            'id' => $targetUser->id,
            'month' => '2026-03',
        ]));

        $list->assertStatus(200);

        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);
        $list->assertSee('href="' . $detailUrl . '"', false);

        $detail = $this->actingAs($admin)->get($detailUrl);

        $detail->assertStatus(200);
        $detail->assertSee('勤怠詳細', false);
        $detail->assertSee('山田 太郎', false);
        $detail->assertSee('2026年', false);
        $detail->assertSee('3月15日', false);
        $detail->assertSee('value="09:00"', false);
        $detail->assertSee('value="18:00"', false);
        $detail->assertSee('value="12:00"', false);
        $detail->assertSee('value="13:00"', false);
        $detail->assertSee('通常勤務', false);
    }
}
