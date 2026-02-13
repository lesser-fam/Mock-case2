<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $from = $request->input('from');
        return redirect($from === 'admin' ? '/admin/login' : '/login');
    }
}