@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container--narrow attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    @include('shared.attendance_detail_table', [
    'person' => $request->applicant,
    'yearLabel' => $yearLabel,
    'mdLabel' => $mdLabel,
    'displayWorkStart' => $displayWorkStart,
    'displayWorkEnd' => $displayWorkEnd,
    'breakRows' => $breakRows,
    'displayMemo' => $displayMemo,
    ])

    <div class="detail-actions">
        @if($isPending)
        <form id="approveForm" method="POST" action="{{ route('request.approve.store', ['attendance_correction_request_id' => $request->id]) }}">
            @csrf
            <button type="submit" class="btn btn--approve btn--black">承認</button>
        </form>
        @else
        <p class="btn btn--approve btn--gray is-disable">承認済み</p>
        @endif
    </div>
</div>
@endsection