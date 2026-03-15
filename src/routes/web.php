<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\UserAttendanceDetailController;
use App\Http\Controllers\UserAttendanceMonthController;
use App\Http\Controllers\UserAttendanceStampController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminDailyAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\StampCorrectionRequestController;



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

    return redirect()->route('attendance.stamp.show');
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
    Route::get('/attendance', [UserAttendanceStampController::class, 'show'])->name('attendance.stamp.show');

    Route::post('/attendance/work/start', [UserAttendanceStampController::class, 'workStart'])->name('attendance.stamp.work_start');
    Route::post('/attendance/break/start', [UserAttendanceStampController::class, 'breakStart'])->name('attendance.stamp.break_start');
    Route::post('/attendance/break/end', [UserAttendanceStampController::class, 'breakEnd'])->name('attendance.stamp.break_end');
    Route::post('/attendance/work/end', [UserAttendanceStampController::class, 'workEnd'])->name('attendance.stamp.work_end');

    Route::get('/attendance/list', [UserAttendanceMonthController::class, 'index'])->name('attendance.month.index');
    Route::get('/attendance/detail/{id}', [UserAttendanceDetailController::class, 'show'])->name('attendance.detail.show');
    Route::post('/attendance/detail/{id}', [UserAttendanceDetailController::class, 'store'])->name('attendance.detail.store');
});



// ===== 管理者ログイン =====
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});



// ===== 管理者 =====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/attendance/list', [AdminDailyAttendanceController::class, 'index'])->name('attendance.daily.index');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
    Route::patch('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.index');
    Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'staffMonth'])->name('staff.month.index');
    Route::get('/attendance/staff/{id}/csv', [AdminStaffController::class, 'staffMonthCsv'])->name('staff.month.csv');
});




// ===== 申請 =====
Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])->name('request.index');

    Route::get('/stamp_correction_request/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'showForUser'])->name('request.user.show');
});



// ===== 承認 =====
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'show'])->name('request.approve.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'store'])->name('request.approve.store');
});