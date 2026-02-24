<?php

namespace App\Http\Controllers;

use App\Services\Admin\DailyAttendanceTable;
use App\Services\DateQueryParser;
use Illuminate\Http\Request;

class AdminDailyAttendanceController extends Controller
{
    public function __construct(
        private DateQueryParser $parser,
        private DailyAttendanceTable $table
    ) {}

    public function index(Request $request)
    {
        $baseDate = $this->parser->parseDate($request->query('date'));

        return view('admin.attendance_list', $this->table->build($baseDate));
    }
}
