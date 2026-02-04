<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            session(['verify_user_id' => $user->getAuthIdentifier()]);
            Auth::logout();

            return redirect()->route('verification.notice');
        }

        if ($user && $request->is('admin/login')) {
            return redirect('/admin/attendance/list');
        }

        return redirect('/attendance');
    }
}
