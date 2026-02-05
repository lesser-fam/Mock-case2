<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login.store');
});

Route::get('/email/verify', function () {
    if (!session('verify_user_id')) {
        abort(403);
    }
    return view('user.verify');
})->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function ($id,  $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    Auth::login($user);

    session()->forget('verify_user_id');

    return redirect('/attendance');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/resend', function () {
    $userId = session('verify_user_id');

    if (! $userId) {
        abort(403);
    }

    $user = User::findOrFail($userId);

    if ($user->hasVerifiedEmail()) {
        return redirect('/login');
    }

    $user->sendEmailVerificationNotification();

    return back()->with('resent', true);
})->name('verification.resend');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/work/start', [AttendanceController::class, 'workStart'])->name('attendance.work.start');
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])->name('attendance.break.start');
    Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd'])->name('attendance.break.end');
    Route::post('/attendance/work/end', [AttendanceController::class, 'workEnd'])->name('attendance.work.end');

    //ダミー
    Route::get('/attendance/list', fn() => 'todo')->name('attendance.list');
    Route::get('/stamp_correction_request/list', fn() => 'todo')->name('request.list');
});