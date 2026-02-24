<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use App\Services\Attendance\CorrectionApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function __construct(private CorrectionApprovalService $approval) {}

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

        $this->approval->approve((int) $attendance_correction_request_id, (int) $admin->id);

        return redirect()->route('request.approve.show', compact('attendance_correction_request_id'));
    }
}
