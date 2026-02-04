<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user) {
            session(['verify_user_id' => $user->id]);
            Auth::logout();
        }

        return redirect()->route('verification.notice');
    }
}
