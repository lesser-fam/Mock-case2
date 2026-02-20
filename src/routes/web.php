<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\StampCorrectionRequestController;


// ===== 管理者ログイン =====
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// ===== メール認証（一般ユーザー登録用） =====
Route::get('/email/verify', function () {
    if (!session('verify_user_id')) abort(403);
    return view('user.verify');
})->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function ($id,  $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals($hash, sha1($user->getEmailForVerification()))) abort(403);

    if (! $user->hasVerifiedEmail()) $user->markEmailAsVerified();

    Auth::login($user);
    session()->forget('verify_user_id');

    return redirect()->route('attendance');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/resend', function () {
    $userId = session('verify_user_id');
    if (! $userId) abort(403);

    $user = User::findOrFail($userId);
    if ($user->hasVerifiedEmail()) return redirect('/login');

    $user->sendEmailVerificationNotification();
    return back()->with('resent', true);
})->name('verification.resend');


// ===== 一般ユーザー =====
Route::middleware(['auth', 'verified', 'user'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');

    Route::post('/attendance/work/start', [AttendanceController::class, 'workStart'])->name('attendance.work.start');
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])->name('attendance.break.start');
    Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd'])->name('attendance.break.end');
    Route::post('/attendance/work/end', [AttendanceController::class, 'workEnd'])->name('attendance.work.end');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'request'])->name('attendance.detail.request');
});


// ===== 申請一覧 =====
Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])->name('request.list');
});


// ===== 申請　承認 =====
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correction_request_id}', [StampCorrectionRequestController::class, 'show'])->name('request.approve.show');
    Route::post('/stamp_correction_request/approve/{attendance_correction_request_id}', [StampCorrectionRequestController::class, 'approve'])->name('request.approve');
});


// ===== 管理者 =====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');
    Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'staffMonth'])->name('staff.attendances');
});
