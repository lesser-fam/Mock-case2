<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminRequestApproveController extends Controller
{
    public function show($attendance_correct_request_id)
    {
        $req = AttendanceCorrectionRequest::query()
            ->with(['applicant', 'attendance', 'breaks'])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.request_approve', [
            'req' => $req,
        ]);
    }

    public function approve($attendance_correct_request_id)
    {
        $admin = Auth::user();

        DB::transaction(function () use ($attendance_correct_request_id, $admin) {
            $req = AttendanceCorrectionRequest::query()
                ->with(['breaks'])
                ->lockForUpdate()
                ->findOrFail($attendance_correct_request_id);

            if ($req->status !== 'pending') {
                abort(409);
            }

            $attendance = Attendance::query()
                ->lockForUpdate()
                ->findOrFail($req->attendance_id);

            $attendance->update([
                'work_start_at' => $req->work_start_at,
                'work_end_at'   => $req->work_end_at,
                'status'        => 'finished',
            ]);

            BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->delete();

            foreach ($req->breaks as $b) {
                BreakTime::create([
                    'attendance_id'   => $attendance->id,
                    'break_start_at'  => $b->break_start_at,
                    'break_end_at'    => $b->break_end_at,
                ]);
            }

            $req->update([
                'status'      => 'approved',
                'approved_by' => $admin->id,
            ]);
        });

        return redirect()->route('request.list', ['status' => 'pending']);
    }
}
