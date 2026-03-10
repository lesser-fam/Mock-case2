<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    /** @test */
    public function 会員登録後に認証メールが送信される(): void
    {
        Notification::fake();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('register'), $this->validPayload())
            ->assertStatus(302);

        $user = User::where('email', 'taro@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function メール認証誘導画面で認証はこちらからボタンを押下するとメール認証サイトに遷移する(): void
    {
        $user = User::factory()->unverified()->create();

        $res = $this->withSession(['verify_user_id' => $user->id])
            ->get(route('verification.notice'));

        $res->assertStatus(200);
        $res->assertSee('認証はこちらから');
        $res->assertSee('href="http://localhost:8025"', false);
    }

    /** @test */
    public function メール認証を完了すると勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->unverified()->create();

        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->withSession(['verify_user_id' => $user->id])
            ->get($signedUrl)
            ->assertRedirect(route('attendance.stamp.show'));

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
