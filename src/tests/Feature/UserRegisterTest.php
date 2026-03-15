<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegisterTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * @test
     */
    public function 名前が未入力の場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/register', $this->validPayload(['name' => '']));

        $res->assertSessionHasErrors(['name']);
        $this->assertSame('お名前を入力してください', session('errors')->first('name'));
    }

    /**
     * @test
     */
    public function メールアドレスが未入力の場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/register', $this->validPayload(['email' => '']));

        $res->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /**
     * @test
     */
    public function パスワードが8文字未満の場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/register', $this->validPayload([
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]));

        $res->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    /**
     * @test
     */
    public function パスワードが一致しない場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/register', $this->validPayload([
            'password_confirmation' => 'passwordxxx',
        ]));

        $res->assertSessionHasErrors(['password_confirmation']);
        $this->assertSame('パスワードと一致しません', session('errors')->first('password_confirmation'));
    }

    /**
     * @test
     */
    public function パスワードが未入力の場合バリデーションメッセージが表示される(): void
    {
        $res = $this->post('/register', $this->validPayload([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $res->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    /**
     * @test
     */
    public function 正しい内容が入力された場合ユーザー情報が保存される(): void
    {
        $this->post('/register', $this->validPayload())->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
            'role' => 'user',
        ]);

        $user = User::where('email', 'taro@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
