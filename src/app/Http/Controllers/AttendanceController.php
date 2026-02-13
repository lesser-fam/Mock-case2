<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequestStoreRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $status = $attendance?->status ?? 'outside';

        return view('user.attendance', [
            'attendance' => $attendance,
            'status' => $status,
            'dateLabel' => Carbon::now()->isoFormat('YYYY年M月D日(ddd)'),
            'timeLabel' => Carbon::now()->format('H:i'),
        ]);
    }

    public function workStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()->firstOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                ['status' => 'outside']
            );

            if ($attendance->status !== 'outside') {
                abort(409);
            }

            $attendance->update([
                'work_start_at' => $now,
                'status' => 'working',
            ]);
        });

        return redirect()->route('attendance');
    }

    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance->status !== 'working') {
                abort(409);
            }

            $hasOpenBreak = BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->lockForUpdate()
                ->exists();

            if ($hasOpenBreak) {
                abort(409);
            }

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start_at' => $now,
            ]);

            $attendance->update(['status' => 'breaking']);
        });

        return redirect()->route('attendance');
    }

    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance->status !== 'breaking') {
                abort(409);
            }

            $latestBreak = BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            $latestBreak->update(['break_end_at' => $now]);
            $attendance->update(['status' => 'working']);
        });

        return redirect()->route('attendance');
    }

    public function workEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        DB::transaction(function () use ($user, $today, $now) {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attendance->status !== 'working') {
                abort(409);
            }

            $openBreak = BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($openBreak) {
                $openBreak->update(['break_end_at' => $now]);
            }

            $attendance->update([
                'work_end_at' => $now,
                'status' => 'finished',
            ]);
        });

        return redirect()->route('attendance');
    }

    public function list(Request $request)
    {
        $user = Auth::user();

        $month = $request->query('month');
        $base = null;
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                $base = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (\Throwable $e) {
                $base = null;
            }
        }
        $base = $base ?: Carbon::now()->startOfMonth();

        $start = $base->copy()->startOfMonth();
        $end   = $base->copy()->endOfMonth();

        DB::transaction(function () use ($user, $start, $end) {
            $existingDates = Attendance::query()
                ->where('user_id', $user->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->all();

            $existingSet = array_flip($existingDates);

            $rows = [];
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $dateStr = $cursor->toDateString();
                if (!isset($existingSet[$dateStr])) {
                    $rows[] = [
                        'user_id' => $user->id,
                        'date' => $dateStr,
                        'status' => 'outside',
                        'work_start_at' => null,
                        'work_end_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $cursor->addDay();
            }

            if (!empty($rows)) {
                Attendance::query()->insert($rows);
            }
        });

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy(fn($a) => $a->date->toDateString());

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dateStr = $cursor->toDateString();
            $attendance = $attendances->get($dateStr);

            $breakMinutes = 0;
            foreach ($attendance->breaks as $b) {
                if ($b->break_start_at && $b->break_end_at) {
                    $breakMinutes += $b->break_start_at->diffInMinutes($b->break_end_at);
                }
            }

            $workMinutes = null;
            if ($attendance->work_start_at && $attendance->work_end_at) {
                $total = $attendance->work_start_at->diffInMinutes($attendance->work_end_at);
                $workMinutes = max(0, $total - $breakMinutes);
            }

            $days[] = [
                'date' => $cursor->copy(),
                'attendance' => $attendance,
                'breakMinutes' => $breakMinutes,
                'workMinutes' => $workMinutes,
            ];

            $cursor->addDay();
        }

        return view('user.attendance_list', [
            'baseMonth' => $base,
            'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
            'days' => $days,
        ]);
    }

    public function detail($id)
    {
        $user = Auth::user();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->with('breaks')
            ->firstOrFail();

        $latestRequest = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->with('breaks')
            ->latest('id')
            ->first();

        $isPending = $latestRequest && $latestRequest->status === 'pending';

        $sourceWorkStart = $isPending ? $latestRequest?->work_start_at : $attendance->work_start_at;
        $sourceWorkEnd = $isPending ? $latestRequest?->work_end_at : $attendance->work_end_at;

        if ($isPending) {
            $breakRows = ($latestRequest?->breaks ?? collect())
                ->sortBy('id')
                ->map(fn($b) => [
                    'start' => $b->break_start_at?->format('H:i'),
                    'end'   => $b->break_end_at?->format('H:i'),
                ])->values()->all();
        } else {
            $breakRows = $attendance->breaks
                ->sortBy('id')
                ->map(fn($b) => [
                    'start' => $b->break_start_at?->format('H:i'),
                    'end'   => $b->break_end_at?->format('H:i'),
                ])->values()->all();

            $breakRows[] = ['start' => null, 'end' => null];
        }

        $displayMemo = $latestRequest?->memo ?? '';
        
        $date = $attendance->date;
        $yearLabel = $date->format('Y年');
        $mdLabel = $date->format('n月j日');

        return view('user.attendance_detail', [
            'attendance' => $attendance,
            'isPending' => $isPending,
            'latestRequest' => $latestRequest,
            'breakRows' => $breakRows,
            'yearLabel' => $yearLabel,
            'mdLabel' => $mdLabel,
            'displayWorkStart' => $sourceWorkStart?->format('H:i'),
            'displayWorkEnd' => $sourceWorkEnd?->format('H:i'),
            'displayMemo' => $displayMemo,
        ]);
    }

    public function request(AttendanceCorrectionRequestStoreRequest $request, $id)
    {
        $user = Auth::user();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // すでに pending があるなら二重申請禁止
        $latestRequest = AttendanceCorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($latestRequest && $latestRequest->status === 'pending') {
            return redirect()->route('attendance.detail', ['id' => $attendance->id]);
        }

        // if ($attendance->status !== 'finished' || !$attendance->work_start_at || !$attendance->work_end_at) {
        //     return back()->withErrors(['work_start_at' => '退勤済みの勤怠のみ修正申請できます'])->withInput();
        // }

        //当日修正禁止なら
        // if ($attendance->date->isSameDay(now())) {
        //     return back()->withErrors(['work_start_at' => '当日の勤怠は修正申請できません'])->withInput();
        // }

        $date = $attendance->date;

        $workStart = $request->input('work_start_at');
        $workEnd   = $request->input('work_end_at');

        $workStartAt = $date->copy()->setTimeFromTimeString($workStart);
        $workEndAt   = $date->copy()->setTimeFromTimeString($workEnd);

        DB::transaction(function () use ($request, $attendance, $user, $date, $workStartAt, $workEndAt) {
            $req = AttendanceCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'       => $user->id,
                'approved_by'   => null,
                'date'          => $date->toDateString(),
                'work_start_at' => $workStartAt,
                'work_end_at'   => $workEndAt,
                'memo'          => $request->input('memo'),
                'status'        => 'pending',
            ]);

            $breaks = $request->input('breaks', []);
            foreach ($breaks as $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                // 「両方ある行だけ保存」
                if (!$bs || !$be) {
                    continue;
                }

                AttendanceCorrectionRequestBreak::create([
                    'request_id'     => $req->id,
                    'break_start_at' => $date->copy()->setTimeFromTimeString($bs),
                    'break_end_at'   => $date->copy()->setTimeFromTimeString($be),
                ]);
            }
        });

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }
}
