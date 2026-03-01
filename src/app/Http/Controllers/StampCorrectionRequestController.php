<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use App\Services\Attendance\CorrectionApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function __construct(private CorrectionApprovalService $approval) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

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

        return view('shared.request_show', $this->buildShowData($req, true));
    }

    public function store(int $attendance_correction_request_id)
    {
        $admin = Auth::user();
        if (!$admin || $admin->role !== 'admin') {
            abort(403);
        }

        $this->approval->approve($attendance_correction_request_id, (int) $admin->id);

        return redirect()->route('request.approve.show', compact('attendance_correction_request_id'));
    }

    public function showForUser(int $attendance_correction_request_id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $req = AttendanceCorrectionRequest::query()
            ->with(['applicant', 'breaks'])
            ->findOrFail($attendance_correction_request_id);

        if ($user->role === 'admin') {
            abort(403);
        }

        if ((int)$req->user_id !== (int)$user->id) {
            abort(403);
        }

        return view('shared.request_show', $this->buildShowData($req, false));
    }

    private function buildShowData(AttendanceCorrectionRequest $req, bool $canApprove): array
    {
        $date = Carbon::parse($req->date);

        $breakRows = $req->breaks
            ->sortBy('id')
            ->map(fn($b) => [
                'start' => $b->break_start_at?->format('H:i'),
                'end'   => $b->break_end_at?->format('H:i'),
            ])->values()->all();

        return [
            'request'           => $req,
            'isPending'         => $req->status === 'pending',
            'canApprove'        => $canApprove,
            'yearLabel'         => $date->format('Y年'),
            'mdLabel'           => $date->format('n月j日'),
            'displayWorkStart'  => $req->work_start_at?->format('H:i'),
            'displayWorkEnd'    => $req->work_end_at?->format('H:i'),
            'displayMemo'       => $req->memo ?? '',
            'breakRows'         => $breakRows,

        ];
    }
}
