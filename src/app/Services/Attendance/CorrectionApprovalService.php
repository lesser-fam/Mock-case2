<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

class CorrectionApprovalService
{
    public function approve(int $requestId, int $adminId): void
    {
        DB::transaction(function () use ($requestId, $adminId) {
            $req = AttendanceCorrectionRequest::query()
                ->with(['breaks'])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($req->status !== 'pending') {
                abort(409);
            }

            $attendance = Attendance::query()
                ->lockForUpdate()
                ->findOrFail($req->attendance_id);

            $attendance->update([
                'work_start_at' => $req->work_start_at,
                'work_end_at'   => $req->work_end_at,
                'memo'          => $req->memo ?? $attendance->memo,
                'status'        => 'finished',
            ]);

            BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->delete();

            foreach ($req->breaks as $b) {
                BreakTime::create([
                    'attendance_id'  => $attendance->id,
                    'break_start_at' => $b->break_start_at,
                    'break_end_at'   => $b->break_end_at,
                ]);
            }

            $req->update([
                'status'      => 'approved',
                'approved_by' => $adminId,
            ]);
        });
    }
}
