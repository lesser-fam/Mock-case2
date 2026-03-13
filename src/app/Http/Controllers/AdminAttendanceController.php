<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceLockedException;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Services\Attendance\AdminAttendanceUpdater;
use Carbon\Carbon;


class AdminAttendanceController extends Controller
{
    public function __construct(private AdminAttendanceUpdater $updater) {}

    public function show($id)
    {
        $attendance = Attendance::query()
            ->with(['breaks', 'user'])
            ->findOrFail($id);

        $pendingRequest = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $isPending = (bool) $pendingRequest;

        $date = $attendance->date instanceof Carbon ? $attendance->date : Carbon::parse($attendance->date);
        $yearLabel = $date->format('Y年');
        $mdLabel   = $date->format('n月j日');

        $breakRows = $attendance->breaks
            ->sortBy('id')
            ->map(fn($b) => [
                'start' => $b->break_start_at?->format('H:i'),
                'end'   => $b->break_end_at?->format('H:i'),
            ])->values()->all();

        $breakRows[] = ['start' => null, 'end' => null];

        return view('admin.attendance_detail', [
            'attendance' => $attendance,
            'yearLabel' => $yearLabel,
            'mdLabel' => $mdLabel,
            'breakRows' => $breakRows,
            'displayWorkStart' => $attendance->work_start_at?->format('H:i'),
            'displayWorkEnd' => $attendance->work_end_at?->format('H:i'),
            'displayMemo' => $attendance->memo ?? '',
            'isPending' => $isPending,
            'pendingRequestId' => $pendingRequest?->id,
        ]);
    }

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::query()
            ->with(['breaks'])
            ->findOrFail($id);

        try {
            $this->updater->updateAttendance(
                $attendance,
                (string) $request->input('work_start_at'),
                (string) $request->input('work_end_at'),
                $request->input('memo'),
                is_array($request->input('breaks')) ? $request->input('breaks') : []
            );
        } catch (AttendanceLockedException $e) {
            return redirect()
                ->route('admin.attendance.show', ['id' => $attendance->id])
                ->withErrors([
                    'common' => $e->getMessage() ?: '承認待ちのため修正はできません'
                ]);
        }

        $date = $attendance->date instanceof Carbon ? $attendance->date : Carbon::parse($attendance->date);

        return redirect()->route('admin.staff.month.index', [
            'id' => $attendance->user_id,
            'month' => $date->format('Y-m'),
        ])->with('status', $date->format('Y年n月j日') . 'の勤怠を保存しました');
    }
}
