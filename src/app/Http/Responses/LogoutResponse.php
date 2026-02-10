<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $fromAdmin = str_starts_with($request->headers->get('referer', ''), url('/admin'));

        return redirect($fromAdmin ? '/admin/login' : '/login');
    }
}