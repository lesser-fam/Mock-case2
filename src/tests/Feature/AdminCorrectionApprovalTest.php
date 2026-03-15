<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionApprovalTest extends TestCase
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
        string $date = '2026-03-03',
        string $start = '09:00',
        string $end = '18:00',
        ?string $memo = '通常勤務'
    ): Attendance {
        return Attendance::factory()
            ->for($user, 'user')
            ->forDate($date)
            ->finished($start, $end)
            ->create([
                'memo' => $memo,
            ]);
    }

    private function addAttendanceBreak(Attendance $attendance, string $start, string $end): void
    {
        BreakTime::factory()
            ->forAttendance($attendance)
            ->timeRange($start, $end)
            ->create();
    }

    private function makeRequest(
        Attendance $attendance,
        User $user,
        array $overrides = []
    ): AttendanceCorrectionRequest {
        return AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($attendance, $user)
            ->pending()
            ->create(array_merge([
                'memo' => '電車遅延のため修正',
                'work_start_at' => Carbon::parse($attendance->date->format('Y-m-d') . ' 09:10:00'),
                'work_end_at' => Carbon::parse($attendance->date->format('Y-m-d') . ' 18:05:00'),
            ], $overrides));
    }

    private function addRequestBreak(
        AttendanceCorrectionRequest $request,
        string $start,
        string $end
    ): void {
        AttendanceCorrectionRequestBreak::factory()
            ->forRequest($request)
            ->timeRange($start, $end)
            ->create();
    }

    /**
     * @test
     */
    public function 承認待ちの修正申請が全て表示されている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 5, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $user1 = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $user2 = $this->user([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
        ]);

        $attendance1 = $this->finishedAttendance($user1, '2026-03-01');
        $attendance2 = $this->finishedAttendance($user2, '2026-03-02');
        $attendance3 = $this->finishedAttendance($user1, '2026-03-03');

        $pending1 = $this->makeRequest($attendance1, $user1, [
            'memo' => '申請A',
            'created_at' => Carbon::parse('2026-03-05 09:00:00'),
        ]);

        $pending2 = $this->makeRequest($attendance2, $user2, [
            'memo' => '申請B',
            'created_at' => Carbon::parse('2026-03-05 10:00:00'),
        ]);

        $approved = $this->makeRequest($attendance3, $user1, [
            'memo' => '承認済申請',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'created_at' => Carbon::parse('2026-03-05 11:00:00'),
        ]);

        $res = $this->actingAs($admin)->get(route('request.index', [
            'status' => 'pending',
        ]));

        $res->assertStatus(200);
        $res->assertSee('承認待ち', false);
        $res->assertSee('山田 太郎', false);
        $res->assertSee('佐藤 花子', false);
        $res->assertSee('2026/03/01', false);
        $res->assertSee('2026/03/02', false);
        $res->assertSee('申請A', false);
        $res->assertSee('申請B', false);
        $res->assertSee($pending1->created_at->format('Y/m/d'), false);
        $res->assertSee($pending2->created_at->format('Y/m/d'), false);

        $res->assertDontSee('承認済申請', false);

        $res->assertSee(route('request.approve.show', [
            'attendance_correct_request_id' => $pending1->id,
        ]), false);

        $res->assertSee(route('request.approve.show', [
            'attendance_correct_request_id' => $pending2->id,
        ]), false);
    }

    /**
     * @test
     */
    public function 承認済みの修正申請が全て表示されている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 5, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $user1 = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $user2 = $this->user([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
        ]);

        $attendance1 = $this->finishedAttendance($user1, '2026-03-01');
        $attendance2 = $this->finishedAttendance($user2, '2026-03-02');
        $attendance3 = $this->finishedAttendance($user1, '2026-03-03');

        $approved1 = AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($attendance1, $user1)
            ->approved($admin)
            ->create([
                'memo' => '承認済A',
                'created_at' => Carbon::parse('2026-03-05 09:00:00'),
            ]);

        $approved2 = AttendanceCorrectionRequest::factory()
            ->forAttendanceAndUser($attendance2, $user2)
            ->approved($admin)
            ->create([
                'memo' => '承認済B',
                'created_at' => Carbon::parse('2026-03-05 10:00:00'),
            ]);

        $this->makeRequest($attendance3, $user1, [
            'memo' => '未承認申請',
            'created_at' => Carbon::parse('2026-03-05 11:00:00'),
        ]);

        $res = $this->actingAs($admin)->get(route('request.index', [
            'status' => 'approved',
        ]));

        $res->assertStatus(200);
        $res->assertSee('承認済み', false);
        $res->assertSee('山田 太郎', false);
        $res->assertSee('佐藤 花子', false);
        $res->assertSee('2026/03/01', false);
        $res->assertSee('2026/03/02', false);
        $res->assertSee('承認済A', false);
        $res->assertSee('承認済B', false);
        $res->assertSee($approved1->created_at->format('Y/m/d'), false);
        $res->assertSee($approved2->created_at->format('Y/m/d'), false);

        $res->assertDontSee('未承認申請', false);

        $res->assertSee(route('request.approve.show', [
            'attendance_correct_request_id' => $approved1->id,
        ]), false);

        $res->assertSee(route('request.approve.show', [
            'attendance_correct_request_id' => $approved2->id,
        ]), false);
    }

    /**
     * @test
     */
    public function 修正申請の詳細内容が正しく表示されている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $user = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $attendance = $this->finishedAttendance($user, '2026-03-03', '09:00', '18:00', '元の備考');

        $request = $this->makeRequest($attendance, $user, [
            'work_start_at' => Carbon::parse('2026-03-03 09:10:00'),
            'work_end_at' => Carbon::parse('2026-03-03 18:05:00'),
            'memo' => '電車遅延のため修正',
        ]);

        $this->addRequestBreak($request, '12:30', '13:00');

        $res = $this->actingAs($admin)->get(route('request.approve.show', [
            'attendance_correct_request_id' => $request->id,
        ]));

        $res->assertStatus(200);
        $res->assertSee('勤怠詳細', false);
        $res->assertSee('山田 太郎', false);
        $res->assertSee('2026年', false);
        $res->assertSee('3月3日', false);
        $res->assertSee('09:10', false);
        $res->assertSee('18:05', false);
        $res->assertSee('12:30', false);
        $res->assertSee('13:00', false);
        $res->assertSee('電車遅延のため修正', false);
        $res->assertSee('承認', false);
    }

    /**
     * @test
     */
    public function 修正申請の承認処理が正しく行われる(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $user = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $attendance = $this->finishedAttendance($user, '2026-03-03', '09:00', '18:00', '修正前メモ');
        $this->addAttendanceBreak($attendance, '12:00', '13:00');

        $request = $this->makeRequest($attendance, $user, [
            'work_start_at' => Carbon::parse('2026-03-03 09:10:00'),
            'work_end_at' => Carbon::parse('2026-03-03 18:05:00'),
            'memo' => '修正後メモ',
        ]);

        $this->addRequestBreak($request, '12:30', '13:00');
        $this->addRequestBreak($request, '15:00', '15:15');

        $res = $this->actingAs($admin)->post(route('request.approve.store', [
            'attendance_correct_request_id' => $request->id,
        ]));

        $res->assertStatus(302);
        $res->assertRedirect(route('request.approve.show', [
            'attendance_correct_request_id' => $request->id,
        ]));

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $attendance->refresh();

        $this->assertSame('09:10', $attendance->work_start_at?->format('H:i'));
        $this->assertSame('18:05', $attendance->work_end_at?->format('H:i'));
        $this->assertSame('修正後メモ', $attendance->memo);
        $this->assertSame('finished', $attendance->status);

        $breaks = BreakTime::where('attendance_id', $attendance->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $breaks);
        $this->assertSame('12:30', $breaks[0]->break_start_at?->format('H:i'));
        $this->assertSame('13:00', $breaks[0]->break_end_at?->format('H:i'));
        $this->assertSame('15:00', $breaks[1]->break_start_at?->format('H:i'));
        $this->assertSame('15:15', $breaks[1]->break_end_at?->format('H:i'));

        $detail = $this->actingAs($admin)->get(route('request.approve.show', [
            'attendance_correct_request_id' => $request->id,
        ]));

        $detail->assertStatus(200);
        $detail->assertSee('承認済み', false);
    }
}
