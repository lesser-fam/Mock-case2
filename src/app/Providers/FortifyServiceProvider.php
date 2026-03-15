<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;


class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('user.register');
        });

        Fortify::loginView(function (Request $request) {
            return $request->is('admin/login')
                ? view('admin.login')
                : view('user.login');
        });

        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by(
                (string) $request->email . $request->ip()
            );
        });

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            $failed = function () {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            };

            if (! $user || ! Hash::check($request->password, $user->password)) {
                $failed();
            }

            if ($request->is('admin/login')) {
                if ($user->role !== 'admin') $failed();

                $request->session()->put('login_context', 'admin');
                return $user;
            }

            if ($user->role !== 'user') $failed();

            $request->session()->put('login_context', 'user');
            return $user;
        });
    }
}
