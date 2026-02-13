<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $status = $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        $query = AttendanceCorrectionRequest::query()
            ->with(['applicant'])
            ->where('status', $status);

        if ($status === 'pending') {
            $query->orderBy('date', 'asc')->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('date', 'desc')->orderBy('created_at', 'desc');
        }

        $isAdmin = ($user->role === 'admin');

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $requests = $query->paginate(10)->appends($request->query());

        return view('shared.request_list', [
            'requests' => $requests,
            'status' => $status,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show($attendance_correction_request_id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        $req = AttendanceCorrectionRequest::query()
            ->with(['applicant', 'approver', 'attendance', 'breaks'])
            ->findOrFail($attendance_correction_request_id);

        $date = $req->date;
        $yearLabel = \Carbon\Carbon::parse($date)->format('Y年');
        $mdLabel   = \Carbon\Carbon::parse($date)->format('n月j日');

        $breakRows = $req->breaks
            ->sortBy('id')
            ->map(fn($b) => [
                'start' => $b->break_start_at?->format('H:i'),
                'end'   => $b->break_end_at?->format('H:i'),
            ])->values()->all();

        return view('admin.request_approve', [
            'request' => $req,
            'isPending' => $req->status === 'pending',
            'displayWorkStart' => optional($req->work_start_at)->format('H:i'),
            'displayWorkEnd'   => optional($req->work_end_at)->format('H:i'),
            'displayMemo'      => $req->memo ?? '',
            'breakRows' => $breakRows,
            'yearLabel' => $yearLabel,
            'mdLabel'   => $mdLabel,
        ]);
    }

    public function approve($attendance_correction_request_id)
    {
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            abort(403);
        }

        DB::transaction(function () use ($attendance_correction_request_id, $admin) {

            $req = AttendanceCorrectionRequest::query()
                ->with(['attendance', 'breaks'])
                ->lockForUpdate()
                ->findOrFail($attendance_correction_request_id);

            if ($req->status !== 'pending') {
                abort(409);
            }

            $attendance = $req->attendance;

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
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $b->break_start_at,
                    'break_end_at'   => $b->break_end_at,
                ]);
            }

            $req->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                // 'approved_at' => now(),
            ]);
        });

        return redirect()->route('request.list', ['status' => 'pending']);
    }
}
