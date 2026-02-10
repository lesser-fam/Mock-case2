<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function list()
    {
        return view('admin.attendance_list');
    }
}
