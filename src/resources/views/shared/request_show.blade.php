@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container--narrow attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <table class="attendance-detail__table">
        <tr class="attendance-detail__table-row">
            <th class="attendance-detail__table-key">名前</th>
            <td class="attendance-detail__cell">
                <span class="name-split cell-box">{{ $correctionRequest->applicant?->name ?? '' }}</span>
            </td>
            <td class="attendance-detail__cell is-empty"></td>
            <td class="attendance-detail__cell is-empty"></td>
        </tr>

        <tr class="attendance-detail__table-row">
            <th class="attendance-detail__table-key">日付</th>
            <td class="attendance-detail__cell">
                <span class="cell-box">{{ $yearLabel }}</span>
            </td>
            <td class="attendance-detail__cell is-empty"></td>
            <td class="attendance-detail__cell">
                <span class="cell-box">{{ $mdLabel }}</span>
            </td>
        </tr>

        <tr class="attendance-detail__table-row">
            <th class="attendance-detail__table-key">出勤・退勤</th>
            <td class="attendance-detail__cell">
                <span class="time-display">{{ $displayWorkStart ?? '' }}</span>
            </td>
            <td class="attendance-detail__cell sep">〜</td>
            <td class="attendance-detail__cell">
                <span class="time-display">{{ $displayWorkEnd ?? '' }}</span>
            </td>
        </tr>

        @foreach ($breakRows as $i => $row)
        <tr class="attendance-detail__table-row">
            <th class="attendance-detail__table-key">休憩{{ $i === 0 ? '' : $i + 1 }}</th>
            <td class="attendance-detail__cell">
                <span class="time-display">{{ $row['start'] ?? '' }}</span>
            </td>
            <td class="attendance-detail__cell sep">～</td>
            <td class="attendance-detail__cell">
                <span class="time-display">{{ $row['end'] ?? '' }}</span>
            </td>
        </tr>
        @endforeach

        <tr class="attendance-detail__table-row">
            <th class="attendance-detail__table-key">備考</th>
            <td class="attendance-detail__cell memo-cell" colspan="3">
                <span class="memo-display">{{ $displayMemo ?? '' }}</span>
            </td>
        </tr>
    </table>

    <div class="detail-actions">
        @if(($canApprove ?? false) && ($isPending ?? false))
        <form method="POST" action="{{ route('request.approve.store', ['attendance_correction_request_id' => $correctionRequest->id]) }}">
            @csrf
            <button type="submit" class="btn btn--approve btn--black">承認</button>
        </form>
        @else
        <p class="btn btn--approve btn--gray is-disable">{{ ($isPending ?? false) ? '承認待ち' : '承認済み' }}</p>
        @endif
    </div>
</div>
@endsection