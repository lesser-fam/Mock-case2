<?php

namespace App\Http\Controllers;

use App\Services\Attendance\AttendanceMonthTable;
use App\Services\DateQueryParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAttendanceMonthController extends Controller
{
    public function __construct(private DateQueryParser $parser) {}

    public function index(Request $request, AttendanceMonthTable $table)
    {
        $baseMonth = $this->parser->parseMonth($request->query('month'));

        return view('user.attendance_list', $table->build(Auth::id(), $baseMonth));
    }
}