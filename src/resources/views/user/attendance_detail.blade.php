@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_detail.css') }}">
@endsection

@section('content')

@include('shared.attendance_detail_form', [
'formAction' => route('attendance.detail.request', ['id' => $attendance->id]),
'canEdit' => !$isPending,
'cannotEditMessage' => $isPending ? '*承認待ちのため修正はできません' : '',
'attendance' => $attendance,
'person' => $attendance->user,
'yearLabel' => $yearLabel,
'mdLabel' => $mdLabel,
'displayWorkStart' => $displayWorkStart,
'displayWorkEnd' => $displayWorkEnd,
'breakRows' => $breakRows,
'displayMemo' => $displayMemo,
])

@endsection