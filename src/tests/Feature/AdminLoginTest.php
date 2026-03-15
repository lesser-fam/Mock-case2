<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function registeredAdmin(array $overrides = []): User
    {
        return User::factory()
            ->admin()
            ->withEmail('admin@example.com')
            ->create(array_merge([
                'password' => Hash::make('password'),
            ], $overrides));
    }

    /**
     * @test
     */
    public function メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $res->assertSessionHasErrors(['email']);
        $this->assertSame( 'メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * @test
     */
    public function パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertSame( 'パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * @test
     */
    public function 登録内容と一致しない場合_メールアドレス誤りでログイン失敗メッセージが表示される(): void
    {
        $this->registeredAdmin();

        $res = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $res->assertSessionHasErrors([ 'email' => 'ログイン情報が登録されていません' ]);

        $this->assertGuest();
    }

    /**
     * @test
     */
    public function 登録内容と一致しない場合_パスワード誤りでログイン失敗メッセージが表示される(): void
    {
        $this->registeredAdmin();

        $res = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong',
        ]);

        $res->assertSessionHasErrors([ 'email' => 'ログイン情報が登録されていません' ]);

        $this->assertGuest();
    }
}
