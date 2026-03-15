<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
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
        $factory = User::factory()->admin()->named('管理者 花子');

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

    private function staff(array $overrides = []): User
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

    private function hmLabel(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    /**
     * @test
     */
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $u1 = $this->staff(['name' => 'ユーザー1']); //勤怠記録有
        $u2 = $this->staff(['name' => 'ユーザー2']); //勤怠未作成

        $date = '2026-03-03';

        $a1 = Attendance::factory()
            ->for($u1, 'user')
            ->forDate($date)
            ->finished('09:00', '18:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($a1)
            ->timeRange('12:00', '13:00')
            ->create();

        $res = $this->actingAs($admin)->get(route('admin.attendance.daily.index', ['date' => $date]));
        $res->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $u2->id,
            'date' => $date,
            'status' => 'outside',
        ]);

        $res->assertViewHas('rows');
        $rows = collect($res->viewData('rows'));

        $r1 = $rows->first(fn($r) => $r['staff']->id === $u1->id);
        $r2 = $rows->first(fn($r) => $r['staff']->id === $u2->id);

        $this->assertNotNull($r1);
        $this->assertNotNull($r2);

        $this->assertSame('ユーザー1', $r1['staff']->name);
        $this->assertSame('09:00', $r1['start']);
        $this->assertSame('18:00', $r1['end']);
        $this->assertSame($this->hmLabel(60), $r1['breakLabel']);
        $this->assertSame($this->hmLabel(480), $r1['workLabel']);

        $this->assertSame('ユーザー2', $r2['staff']->name);
        $this->assertSame('', $r2['start']);
        $this->assertSame('', $r2['end']);
        $this->assertSame('', $r2['breakLabel']);
        $this->assertSame('', $r2['workLabel']);
    }

    /**
     * @test
     */
    public function 遷移した際に現在の日付が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $this->staff(['name' => 'ユーザー1']);

        $res = $this->actingAs($admin)->get(route('admin.attendance.daily.index'));
        $res->assertStatus(200);

        $res->assertSee('2026年3月3日の勤怠', false);
        $res->assertSee('2026/03/03', false);
        $res->assertSee('value="2026-03-03"', false);
    }

    /**
     * @test
     */
    public function 前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $user = $this->staff(['name' => 'ユーザー1']);

        $base = '2026-03-03';
        $prev = '2026-03-02';

        $prevAttendance = Attendance::factory()
            ->for($user, 'user')
            ->forDate($prev)
            ->finished('09:00', '18:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($prevAttendance)
            ->timeRange('12:00', '13:00')
            ->create();

        $baseAttendance = Attendance::factory()
            ->for($user, 'user')
            ->forDate($base)
            ->finished('10:00', '19:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($baseAttendance)
            ->timeRange('13:00', '13:30')
            ->create();

        $res = $this->actingAs($admin)->get(route('admin.attendance.daily.index', ['date' => $base]));
        $res->assertStatus(200);
        $res->assertSee('date=' . $prev, false);

        $res2 = $this->actingAs($admin)->get(route('admin.attendance.daily.index', ['date' => $prev]));
        $res2->assertStatus(200);
        $res2->assertSee('2026年3月2日の勤怠', false);
        $res2->assertSee('2026/03/02', false);
        $res2->assertSee('value="2026-03-02"', false);

        $res2->assertSee('ユーザー1', false);
        $res2->assertSee('09:00', false);
        $res2->assertSee('18:00', false);
        $res2->assertSee($this->hmLabel(60), false);
        $res2->assertSee($this->hmLabel(480), false);

        $res2->assertDontSee('10:00', false);
        $res2->assertDontSee('19:00', false);
        $res2->assertDontSee($this->hmLabel(30), false);
        $res2->assertDontSee($this->hmLabel(510), false);
    }

    /**
     * @test
     */
    public function 翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $user = $this->staff(['name' => 'ユーザー1']);

        $base = '2026-03-03';
        $next = '2026-03-04';

        $baseAttendance = Attendance::factory()
            ->for($user, 'user')
            ->forDate($base)
            ->finished('09:00', '18:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($baseAttendance)
            ->timeRange('12:00', '13:00')
            ->create();

        $nextAttendance = Attendance::factory()
            ->for($user, 'user')
            ->forDate($next)
            ->finished('10:00', '19:00')
            ->create();

        BreakTime::factory()
            ->forAttendance($nextAttendance)
            ->timeRange('13:00', '13:30')
            ->create();

        $res = $this->actingAs($admin)->get(route('admin.attendance.daily.index', ['date' => $base]));
        $res->assertStatus(200);
        $res->assertSee('date=' . $next, false);

        $res2 = $this->actingAs($admin)->get(route('admin.attendance.daily.index', ['date' => $next]));
        $res2->assertStatus(200);
        $res2->assertSee('2026年3月4日の勤怠', false);
        $res2->assertSee('2026/03/04', false);
        $res2->assertSee('value="2026-03-04"', false);

        $res2->assertSee('ユーザー1', false);
        $res2->assertSee('10:00', false);
        $res2->assertSee('19:00', false);
        $res2->assertSee($this->hmLabel(30), false);
        $res2->assertSee($this->hmLabel(510), false);

        $res2->assertDontSee('09:00', false);
        $res2->assertDontSee('18:00', false);
        $res2->assertDontSee($this->hmLabel(60), false);
        $res2->assertDontSee($this->hmLabel(480), false);
    }
}
