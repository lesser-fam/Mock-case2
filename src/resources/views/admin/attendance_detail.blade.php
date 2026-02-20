@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_detail.css') }}">
@endsection

@section('content')

@include('shared.attendance_detail_form', [
'formAction' => route('admin.attendance.update', ['id' => $attendance->id]),
'canEdit' => !$isPending,
'cannotEditMessage' => $isPending ? '*承認待ちのため修正はできません' : '',
'pendingLinkUrl' => ($isPending && !empty($pendingRequestId ?? null)) ? route('request.approve.show', ['attendance_correction_request_id' => $pendingRequestId]) : null,
'attendance' => $attendance,
'person' => $attendance->user,
'yearLabel' => $yearLabel,
'mdLabel' => $mdLabel,
'displayWorkStart' => $attendance->work_start_at?->format('H:i'),
'displayWorkEnd' => $attendance->work_end_at?->format('H:i'),
'breakRows' => $breakRows,
'displayMemo' => $attendance->memo ?? '',
])

@endsection