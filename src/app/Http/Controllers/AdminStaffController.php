<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceMonthTable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStaffController extends Controller
{
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

        $monthStr = $request->query('month');
        $base = null;


        if (is_string($monthStr) && preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
            try {
                $base = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
            } catch (\Throwable $e) {
                $base = null;
            }
        }

        $base = $base ?: now()->startOfMonth();

        $data = $table->build($staff->id, $base);

        return view('admin.staff_attendance_list', array_merge($data, [
            'staff' => $staff,
            'listRouteName' => 'admin.staff.attendances',
            'detailRouteName' => 'admin.attendance.detail',
            'listRouteParams' => ['id' => $staff->id],
            'detailRouteParams' => [],
        ]));
    }
}