<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AdminAttendanceController extends Controller
{
    public function list(Request $request)
    {
        $dateStr = $request->query('date');

        try {
            $baseDate = $dateStr
                ? Carbon::createFromFormat('Y-m-d', $dateStr)->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable $e) {
            $baseDate = now()->startOfDay();
        }

        $staffs = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        $staffIds = $staffs->pluck('id')->all();
        $dateKey = $baseDate->toDateString();

        // その日の attendance を既存分だけ取得
        $existing = Attendance::query()
            ->whereDate('date', $dateKey)
            ->whereIn('user_id', $staffIds)
            ->get()
            ->keyBy('user_id');

        // 無いスタッフ分は作ってしまう（未出勤でも詳細を開けるため）
        DB::transaction(function () use ($staffs, $existing, $dateKey) {
            foreach ($staffs as $staff) {
                if ($existing->has($staff->id)) continue;

                Attendance::create([
                    'user_id' => $staff->id,
                    'date' => $dateKey,
                    'status' => 'outside',
                    'work_start_at' => null,
                    'work_end_at' => null,
                    'memo' => null,
                ]);
            }
        });

        // もう一回、全員分を breaks と user 付きで取得
        $attendances = Attendance::query()
            ->whereDate('date', $dateKey)
            ->whereIn('user_id', $staffIds)
            ->with(['breaks', 'user'])
            ->get()
            ->keyBy('user_id');

        $rows = [];
        foreach ($staffs as $staff) {
            /** @var Attendance $a */
            $a = $attendances->get($staff->id);

            $start = $a?->work_start_at ? $a->work_start_at->format('H:i') : '';
            $end   = $a?->work_end_at ? $a->work_end_at->format('H:i') : '';

            $breakMin = 0;
            if ($a && $a->relationLoaded('breaks')) {
                $breakMin = $a->breaks->sum(function ($b) {
                    if (!$b->break_start_at || !$b->break_end_at) return 0;
                    return $b->break_start_at->diffInMinutes($b->break_end_at);
                });
            }

            $workMin = null;
            if ($a && $a->work_start_at && $a->work_end_at) {
                $workMin = $a->work_start_at->diffInMinutes($a->work_end_at) - $breakMin;
                if ($workMin < 0) $workMin = 0;
            }

            $breakLabel = $breakMin ? sprintf('%d:%02d', intdiv($breakMin, 60), $breakMin % 60) : '';
            $workLabel  = is_null($workMin) ? '' : sprintf('%d:%02d', intdiv($workMin, 60), $workMin % 60);

            $rows[] = [
                'staff' => $staff,
                'attendance' => $a,
                'start' => $start,
                'end' => $end,
                'breakLabel' => $breakLabel,
                'workLabel' => $workLabel,
            ];
        }

        return view('admin.attendance_list', [
            'baseDate' => $baseDate,
            'prevDate' => $baseDate->copy()->subDay()->toDateString(),
            'nextDate' => $baseDate->copy()->addDay()->toDateString(),
            'rows' => $rows,
        ]);
    }

    public function detail($id)
    {
        $attendance = Attendance::query()
            ->with(['breaks', 'user'])
            ->findOrFail($id);

        $pendingReq = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $isPending = (bool) $pendingReq;

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
            'pendingRequestId' => $pendingReq?->id,
        ]);
    }

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::query()
            ->with(['breaks'])
            ->findOrFail($id);

        $pendingReq = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingReq) {
            return redirect()
                ->route('admin.attendance.detail', ['id' => $attendance->id])
                ->withErrors(['common' => '承認待ちのため修正はできません']);
        }

        $date = $attendance->date instanceof Carbon ? $attendance->date->copy() : Carbon::parse($attendance->date);

        $workStartAt = $date->copy()->setTimeFromTimeString($request->input('work_start_at'));
        $workEndAt   = $date->copy()->setTimeFromTimeString($request->input('work_end_at'));

        $breaksInput = $request->input('breaks', []);
        if (!is_array($breaksInput)) $breaksInput = [];

        $breakRows = [];
        foreach ($breaksInput as $b) {
            $bs = $b['start'] ?? null;
            $be = $b['end'] ?? null;
            if (!$bs || !$be) continue;

            $breakRows[] = [
                'start' => $date->copy()->setTimeFromTimeString($bs),
                'end'   => $date->copy()->setTimeFromTimeString($be),
            ];
        }

        DB::transaction(function () use ($attendance, $request, $workStartAt, $workEndAt, $breakRows) {
            $attendance->update([
                'work_start_at' => $workStartAt,
                'work_end_at'   => $workEndAt,
                'memo'          => $request->input('memo'),
                'status'        => 'finished',
            ]);

            BreakTime::query()->where('attendance_id', $attendance->id)->delete();

            foreach ($breakRows as $b) {
                BreakTime::create([
                    'attendance_id'  => $attendance->id,
                    'break_start_at' => $b['start'],
                    'break_end_at'   => $b['end'],
                ]);
            }
        });

        return redirect()->route('admin.staff.attendances', [
            'id' => $attendance->user_id,
            'month' => $date->format('Y-m'),
        ])->with('status', $date->format('Y年n月j日') . 'の勤怠を保存しました');
    }
}