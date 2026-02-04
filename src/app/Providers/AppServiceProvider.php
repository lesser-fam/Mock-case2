<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            $role = $user?->role; // 'user' or 'admin'

            $status = 'outside';

            if ($user && $role === 'user') {
                $today = Carbon::today()->toDateString();

                $todayAttendance = Attendance::query()
                    ->where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();

                if ($todayAttendance) {
                    $status = $todayAttendance->status; // outside/working/breaking/finished
                }
            }

            $view->with([
                'navRole' => $role,
                'navStatus' => $status,
            ]);
        });
    }
}
