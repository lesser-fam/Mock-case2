<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Attendance\AttendanceMonthTable;
use App\Services\DateQueryParser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function staffMonthCsv(Request $request, AttendanceMonthTable $table, $id): StreamedResponse
    {
        $staff = User::query()
            ->where('role', 'user')
            ->findOrFail($id);

        $base = $this->parser->parseMonth($request->query('month'));

        $data = $table->build($staff->id, $base);
        $days = $data['days'];

        $filename = sprintf(
            '%s_%s_attendance.csv',
            str_replace(' ', '_', $staff->name),
            $base->format('Y_m')
        );

        return response()->streamDownload(function () use ($staff, $base, $days) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['氏名', $staff->name]);
            fputcsv($handle, ['メールアドレス', $staff->email]);
            fputcsv($handle, ['対象月', $base->format('Y年m月')]);
            fputcsv($handle, []);

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($days as $row) {
                fputcsv($handle, [
                    $row['dateLabel'] ?? '',
                    $row['start'] ?? '',
                    $row['end'] ?? '',
                    $row['breakLabel'] ?? '',
                    $row['workLabel'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
