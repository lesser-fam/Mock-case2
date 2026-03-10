<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffMonthCsvTest extends TestCase
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
            $factory = $factory->named($overrides['name']);
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
        $factory = User::factory()->user()->named('山田 太郎')->withEmail('yamada@example.com');

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
    public function 管理者はスタッフ別月次勤怠一覧をCSV出力できる(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $staff = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $other = $this->user([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
        ]);

        $a1 = $this->finishedAttendance($staff, '2026-03-01', '09:00', '18:00');
        $this->addBreak($a1, '12:00', '13:00');

        $a2 = $this->finishedAttendance($staff, '2026-03-15', '10:00', '19:00');
        $this->addBreak($a2, '13:00', '13:30');

        $otherAttendance = $this->finishedAttendance($other, '2026-03-15', '08:00', '17:00');
        $this->addBreak($otherAttendance, '12:00', '13:00');

        $res = $this->actingAs($admin)->get(route('admin.staff.month.csv', [
            'id' => $staff->id,
            'month' => '2026-03',
        ]));

        $res->assertStatus(200);
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $disposition = $res->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('.csv', $disposition);

        $content = $res->streamedContent();

        $this->assertNotSame('', $content);

        $this->assertTrue(str_starts_with($content, "\xEF\xBB\xBF"));

        $contentWithoutBom = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $this->assertNotNull($contentWithoutBom);

        $normalized = str_replace("\r\n", "\n", $contentWithoutBom);

        $this->assertStringContainsString('日付,出勤,退勤,休憩,合計', $normalized);

        $this->assertStringContainsString('03/01(日),09:00,18:00,1:00,8:00', $normalized);
        $this->assertStringContainsString('03/15(日),10:00,19:00,0:30,8:30', $normalized);

        $this->assertStringNotContainsString('08:00,17:00', $normalized);
        $this->assertStringNotContainsString('佐藤 花子', $normalized);
    }

    /**
     * @test
     */
    public function 一般ユーザーはスタッフ別月次勤怠一覧CSV出力にアクセスできない(): void
    {
        $user = $this->user();
        $targetStaff = $this->user([
            'name' => '対象ユーザー',
            'email' => 'target@example.com',
        ]);

        $res = $this->actingAs($user)->get(route('admin.staff.month.csv', [
            'id' => $targetStaff->id,
            'month' => '2026-03',
        ]));

        $res->assertStatus(403);
    }

    /**
     * @test
     */
    public function 指定月のデータだけがCSV出力される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = $this->admin();

        $staff = $this->user([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $feb = $this->finishedAttendance($staff, '2026-02-20', '09:00', '18:00');
        $this->addBreak($feb, '12:00', '13:00');

        $mar = $this->finishedAttendance($staff, '2026-03-15', '10:00', '19:00');
        $this->addBreak($mar, '13:00', '13:30');

        $apr = $this->finishedAttendance($staff, '2026-04-01', '08:30', '17:30');
        $this->addBreak($apr, '12:00', '13:00');

        $res = $this->actingAs($admin)->get(route('admin.staff.month.csv', [
            'id' => $staff->id,
            'month' => '2026-03',
        ]));

        $res->assertStatus(200);

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $res->streamedContent());
        $this->assertNotNull($content);

        $normalized = str_replace("\r\n", "\n", $content);

        $this->assertStringContainsString('03/15(日),10:00,19:00,0:30,8:30', $normalized);
        $this->assertStringNotContainsString('02/20(金),09:00,18:00,1:00,8:00', $normalized);
        $this->assertStringNotContainsString('04/01(水),08:30,17:30,1:00,8:00', $normalized);
    }
}
