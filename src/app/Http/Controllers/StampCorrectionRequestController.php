<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->with(['applicant']) // applicant() をモデルで定義済み
            ->where('status', $status)
            ->orderByDesc('created_at');

        $isAdmin = ($user->role === 'admin');

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('shared.request_list', [
            'requests' => $requests,
            'status' => $status,
            'isAdmin' => $isAdmin,
        ]);
    }
}
