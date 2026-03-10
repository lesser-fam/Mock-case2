<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceCorrectionRequestTest extends TestCase
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

    private function admin(array $overrides = []): User
    {
        $factory = User::factory()->admin()->named('管理者 太郎');

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

    private function finishedAttendance(User $user, string $date = '2026-03-03'): Attendance
    {
        return Attendance::factory()
            ->for($user, 'user')
            ->forDate($date)
            ->finished('09:00', '18:00')
            ->create();
    }

    private function postCorrection(Attendance $attendance, User $user, array $payload)
    {
        return $this->actingAs($user)->post(
            route('attendance.detail.request', ['id' => $attendance->id]),
            $payload
        );
    }

    /**
     * @test
     */
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user);

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '19:00',
            'work_end_at' => '18:00',
            'memo' => '修正します',
            'breaks' => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['work_start_at']);
        $this->assertSame(
            '出勤時間もしくは退勤時間が不適切な値です',
            session('errors')->first('work_start_at')
        );
    }

    /**
     * @test
     */
    public function 退勤時間が出勤時間より前になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user);

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:00',
            'work_end_at' => '08:00',
            'memo' => '修正します',
            'breaks' => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['work_start_at']);
        $this->assertSame(
            '出勤時間もしくは退勤時間が不適切な値です',
            session('errors')->first('work_start_at')
        );
    }

    /**
     * @test
     */
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user);

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '修正します',
            'breaks' => [
                ['start' => '18:30', 'end' => '18:40'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0']);
        $this->assertSame(
            '休憩時間が不適切な値です',
            session('errors')->first('breaks.0')
        );
    }

    /**
     * @test
     */
    public function 休憩開始時間が出勤時間より前になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user);

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '修正します',
            'breaks' => [
                ['start' => '08:50', 'end' => '09:10'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0']);
        $this->assertSame(
            '休憩時間が不適切な値です',
            session('errors')->first('breaks.0')
        );
    }

    /**
     * @test
     */
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user);

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '修正します',
            'breaks' => [
                ['start' => '17:50', 'end' => '18:10'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['breaks.0']);
        $this->assertSame(
            '休憩時間もしくは退勤時間が不適切な値です',
            session('errors')->first('breaks.0')
        );
    }

    /**
     * @test
     */
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user);

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:00',
            'work_end_at' => '18:00',
            'memo' => '',
            'breaks' => [],
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors(['memo']);
        $this->assertSame(
            '備考を記入してください',
            session('errors')->first('memo')
        );
    }

    /**
     * @test
     */
    public function 修正申請処理が実行される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $admin = $this->admin();
        $attendance = $this->finishedAttendance($user, '2026-03-03');

        $res = $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:10',
            'work_end_at' => '18:05',
            'memo' => '遅延のため修正申請',
            'breaks' => [
                ['start' => '12:30', 'end' => '13:00'],
            ],
        ]);

        $res->assertStatus(302);
        $res->assertRedirect(route('attendance.detail.show', ['id' => $attendance->id]));

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'date' => '2026-03-03',
            'memo' => '遅延のため修正申請',
        ]);

        $req = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($req);

        $this->assertDatabaseHas('attendance_correction_request_breaks', [
            'request_id' => $req->id,
        ]);

        $approveShow = $this->actingAs($admin)->get(
            route('request.approve.show', ['attendance_correction_request_id' => $req->id])
        );
        $approveShow->assertStatus(200);
        $approveShow->assertSee('遅延のため修正申請', false);
        $approveShow->assertSee('2026年', false);
        $approveShow->assertSee('3月3日', false);
        $approveShow->assertSee('09:10', false);
        $approveShow->assertSee('18:05', false);
        $approveShow->assertSee('12:30', false);
        $approveShow->assertSee('13:00', false);

        $pendingList = $this->actingAs($admin)->get(route('request.index', ['status' => 'pending']));
        $pendingList->assertStatus(200);
        $pendingList->assertViewHas('requests');

        $p = $pendingList->viewData('requests');
        $this->assertTrue($p->getCollection()->pluck('id')->contains($req->id));
        $pendingList->assertSee('承認待ち', false);
        $pendingList->assertSee('山田 太郎', false);
        $pendingList->assertSee('2026/03/03', false);
        $pendingList->assertSee('遅延のため修正申請', false);
        $pendingList->assertSee($req->created_at->format('Y/m/d'), false);
    }

    /**
     * @test
     */
    public function 承認待ちにログインユーザーが行った申請が全て表示されている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 5, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $other = $this->user([
            'name' => '他人',
            'email' => 'other@example.com',
        ]);

        $a1 = $this->finishedAttendance($user, '2026-03-01');
        $a2 = $this->finishedAttendance($user, '2026-03-02');
        $a3 = $this->finishedAttendance($other, '2026-03-03');

        AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($a1, $user)
            ->pending()
            ->create(['memo' => '申請1']);

        AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($a2, $user)
            ->pending()
            ->create(['memo' => '申請2']);

        AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($a3, $other)
            ->pending()
            ->create(['memo' => '他人申請']);

        $res = $this->actingAs($user)->get('/stamp_correction_request/list?status=pending');
        $res->assertStatus(200);

        $res->assertViewHas('requests');
        $p = $res->viewData('requests');
        $this->assertSame(2, $p->total());

        $memos = collect($p->items())->pluck('memo')->all();
        $this->assertEqualsCanonicalizing(['申請1', '申請2'], $memos);
    }

    /**
     * @test
     */
    public function 承認済みに管理者が承認した修正申請が全て表示されている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 5, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $admin = $this->admin(['name' => '管理者 花子']);

        $a1 = $this->finishedAttendance($user, '2026-03-01');
        $a2 = $this->finishedAttendance($user, '2026-03-02');
        $a3 = $this->finishedAttendance($user, '2026-03-03');

        AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($a1, $user)
            ->approved($admin)
            ->create(['memo' => '承認済1']);

        AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($a2, $user)
            ->approved($admin)
            ->create(['memo' => '承認済2']);

        AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($a3, $user)
            ->pending()
            ->create(['memo' => '承認待ち']);

        $res = $this->actingAs($user)->get('/stamp_correction_request/list?status=approved');
        $res->assertStatus(200);

        $res->assertViewHas('requests');
        $p = $res->viewData('requests');

        $this->assertSame(2, $p->total());
        $memos = collect($p->items())->pluck('memo')->all();
        $this->assertEqualsCanonicalizing(['承認済1', '承認済2'], $memos);
    }

    /**
     * @test
     */
    public function 各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $user = $this->user();
        $attendance = $this->finishedAttendance($user, '2026-03-03');

        // 申請作成
        $this->postCorrection($attendance, $user, [
            'work_start_at' => '09:10',
            'work_end_at' => '18:05',
            'memo' => '遅延のため修正申請',
            'breaks' => [
                ['start' => '12:30', 'end' => '13:00'],
            ],
        ])->assertStatus(302);

        $req = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($req);

        $list = $this->actingAs($user)->get(route('request.index', ['status' => 'pending']));
        $list->assertStatus(200);

        $expectedHref = route('attendance.detail.show', ['id' => $req->attendance_id]);
        $list->assertSee('href="' . $expectedHref . '"', false);

        $detail = $this->actingAs($user)->get($expectedHref);
        $detail->assertStatus(200);
        $detail->assertSee('勤怠詳細', false);
    }
}
