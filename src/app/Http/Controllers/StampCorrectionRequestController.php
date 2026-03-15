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

    public function show($attendance_correct_request_id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403);
        }

        $correctionRequest = AttendanceCorrectionRequest::query()
            ->with(['applicant', 'approver', 'attendance', 'breaks'])
            ->findOrFail($attendance_correct_request_id);

        return view('shared.request_show', $this->buildShowData($correctionRequest, true));
    }

    public function store(int $attendance_correct_request_id)
    {
        $admin = Auth::user();
        if (!$admin || $admin->role !== 'admin') {
            abort(403);
        }

        $this->approval->approve($attendance_correct_request_id, (int) $admin->id);

        return redirect()->route('request.approve.show', compact('attendance_correct_request_id'));
    }

    public function showForUser(int $attendance_correct_request_id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $correctionRequest = AttendanceCorrectionRequest::query()
            ->with(['applicant', 'breaks'])
            ->findOrFail($attendance_correct_request_id);

        if ($user->role === 'admin') {
            abort(403);
        }

        if ((int)$correctionRequest->user_id !== (int)$user->id) {
            abort(403);
        }

        return view('shared.request_show', $this->buildShowData($correctionRequest, false));
    }

    private function buildShowData(AttendanceCorrectionRequest $correctionRequest, bool $canApprove): array
    {
        $date = Carbon::parse($correctionRequest->date);

        $breakRows = $correctionRequest->breaks
            ->sortBy('id')
            ->map(fn($break) => [
                'start' => $break->break_start_at?->format('H:i'),
                'end'   => $break->break_end_at?->format('H:i'),
            ])
            ->values()
            ->all();

        return [
            'correctionRequest'           => $correctionRequest,
            'isPending'         => $correctionRequest->status === 'pending',
            'canApprove'        => $canApprove,
            'yearLabel'         => $date->format('Y年'),
            'mdLabel'           => $date->format('n月j日'),
            'displayWorkStart'  => $correctionRequest->work_start_at?->format('H:i'),
            'displayWorkEnd'    => $correctionRequest->work_end_at?->format('H:i'),
            'displayMemo'       => $correctionRequest->memo ?? '',
            'breakRows'         => $breakRows,
        ];
    }
}
