<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailUpdateTest extends TestCase
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

    private function admin(array $overrides = []): User
    {
        $factory = User::factory()->admin()->named('管理者 花子');

        if (isset($overrides['name'])) {
            $factory = User::factory()->admin()->named($overrides['name']);
            unset($overrides['name']);
        }

        return $factory->create($overrides);
    }

    private function user(array $overrides = []): User
    {
        $factory = User::factory()->user()->named('山田 太郎');

        if (isset($overrides['name'])) {
            $factory = User::factory()->user()->named($overrides['name']);
            unset($overrides['name']);
        }

        return $factory->create($overrides);
    }

    private function attendanceWithBreaks(User $user, string $date = '2026-03-03'): Attendance
    {
        $attendance = Attendance::factory()
            ->for($user, 'user')
            ->forDate($date)
            ->finished('09:00', '18:00')
            ->create([
                'memo' => '初期メモ',
            ]);

        BreakTime::factory()
            ->forAttendance($attendance)
            ->timeRange('12:00', '12:30')
            ->create();

        return $attendance;
    }

    private function patchAttendance(Attendance $attendance, User $admin, array $payload)
    {
        return $this->actingAs($admin)->patch(
            route('admin.attendance.update', ['id' => $attendance->id]),
            $payload
        );
    }

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $user = $this->user(['name' => '山田 太郎']);
        $attendance = $this->attendanceWithBreaks($user, '2026-03-03');

        $res = $this->actingAs($admin)->get(route('admin.attendance.show', ['id' => $attendance->id]));
        $res->assertStatus(200);

        $res->assertSee('勤怠詳細', false);
        $res->assertSee('山田 太郎', false);
        $res->assertSee('2026年', false);
        $res->assertSee('3月3日', false);
        $res->assertSee('value="09:00"', false);
        $res->assertSee('value="18:00"', false);
        $res->assertSee('value="12:00"', false);
        $res->assertSee('value="12:30"', false);
        $res->assertSee('初期メモ', false);
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $attendance = $this->attendanceWithBreaks($this->user(), '2026-03-03');

        $res = $this->patchAttendance($attendance, $admin, [
            'work_start_at' => '19:00',
            'work_end_at' => '18:00',
            'memo' => '修正',
            'breaks' => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['work_start_at']);
        $this->assertSame('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('work_start_at'));
    }

    /** @test */
    public function 退勤時間が出勤時間より前になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $attendance = $this->attendanceWithBreaks($this->user(), '2026-03-03');

        $res = $this->patchAttendance($attendance, $admin, [
            'work_start_at' => '09:00',
            'work_end_at' => '08:00',
            'memo' => '修正',
            'breaks' => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['work_start_at']);
        $this->assertSame('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('work_start_at'));
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $attendance = $this->attendanceWithBreaks($this->user(), '2026-03-03');

        $res = $this->patchAttendance($attendance, $admin, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '修正',
            'breaks' => [
                ['start' => '18:30', 'end' => '18:40'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0']);
        $this->assertSame('休憩時間が不適切な値です', session('errors')->first('breaks.0'));
    }

    /** @test */
    public function 休憩開始時間が出勤時間より前になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $attendance = $this->attendanceWithBreaks($this->user(), '2026-03-03');

        $res = $this->patchAttendance($attendance, $admin, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '修正',
            'breaks' => [
                ['start' => '08:50', 'end' => '09:10'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0']);
        $this->assertSame('休憩時間が不適切な値です', session('errors')->first('breaks.0'));
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $attendance = $this->attendanceWithBreaks($this->user(), '2026-03-03');

        $res = $this->patchAttendance($attendance, $admin, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '修正',
            'breaks' => [
                ['start' => '17:50', 'end' => '18:10'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0']);
        $this->assertSame('休憩時間もしくは退勤時間が不適切な値です', session('errors')->first('breaks.0'));
    }

    /** @test */
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();
        $attendance = $this->attendanceWithBreaks($this->user(), '2026-03-03');

        $res = $this->patchAttendance($attendance, $admin, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '',
            'breaks' => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['memo']);
        $this->assertSame('備考を記入してください', session('errors')->first('memo'));
    }
}
