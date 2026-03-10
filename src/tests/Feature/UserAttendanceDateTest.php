<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceDateTest extends TestCase
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

    /** @test */
    public function 現在日時がUIと同じ形式で表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 3, 9, 5, 0, 'Asia/Tokyo'));

        $user = User::factory()->user()->create();

        $expectedDate = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $expectedTime = Carbon::now()->format('H:i');

        $res = $this->actingAs($user)->get(route('attendance.stamp.show'));

        $res->assertStatus(200);
        $res->assertSee($expectedDate, false);
        $res->assertSee($expectedTime, false);
    }
}
