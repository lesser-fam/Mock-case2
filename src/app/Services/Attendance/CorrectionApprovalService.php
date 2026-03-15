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
            $correctionRequest = AttendanceCorrectionRequest::query()
                ->with(['breaks'])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($correctionRequest->status !== 'pending') {
                abort(409);
            }

            $attendance = Attendance::query()
                ->lockForUpdate()
                ->findOrFail($correctionRequest->attendance_id);

            $attendance->update([
                'work_start_at' => $correctionRequest->work_start_at,
                'work_end_at'   => $correctionRequest->work_end_at,
                'memo'          => $correctionRequest->memo ?? $attendance->memo,
                'status'        => 'finished',
            ]);

            BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->delete();

            foreach ($correctionRequest->breaks as $b) {
                BreakTime::create([
                    'attendance_id'  => $attendance->id,
                    'break_start_at' => $b->break_start_at,
                    'break_end_at'   => $b->break_end_at,
                ]);
            }

            $correctionRequest->update([
                'status'      => 'approved',
                'approved_by' => $adminId,
            ]);
        });
    }
}
