<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Attendance\AttendanceMonthTable;
use App\Services\DateQueryParser;
use Illuminate\Http\Request;

class AdminStaffController extends Controller
{
    public function __construct(private DateQueryParser $parser) {}

    public function index()
    {
        $staffs = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        return view('admin.staff_list', compact('staffs'));
    }

    public function staffMonth(Request $request, AttendanceMonthTable $table, $id)
    {
        $staff = User::query()
            ->where('role', 'user')
            ->findOrFail($id);

        $base = $this->parser->parseMonth($request->query('month'));

        $data = $table->build($staff->id, $base);

        return view('admin.staff_attendance_list', array_merge($data, [
            'staff' => $staff,
            'listRouteName' => 'admin.staff.month.index',
            'detailRouteName' => 'admin.attendance.show',
            'listRouteParams' => ['id' => $staff->id],
            'detailRouteParams' => [],
        ]));
    }
}
