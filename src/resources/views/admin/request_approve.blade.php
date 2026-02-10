@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <form method="POST" action="{{ route('request.approve', ['attendance_correction_request_id' => $request->id]) }}">
        @csrf

        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $request->applicant?->name ?? '' }}</td>
            </tr>

            <tr>
                <th>日付</th>
                <td class="date-split">
                    <span class="date-split__year">{{ \Carbon\Carbon::parse($request->date)->format('Y年') }}</span>
                    <span class="date-split__md">{{ \Carbon\Carbon::parse($request->date)->format('n月j日') }}</span>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td class="time-range">
                    <span>{{ $displayWorkStart ?? '' }}</span>
                    <span>～</span>
                    <span>{{ $displayWorkEnd ?? '' }}</span>
                </td>
            </tr>

            @foreach ($breakRows as $i => $row)
            <tr>
                <th>休憩{{ $i === 0 ? '' : $i + 1 }}</th>
                <td class="time-range">
                    <span>{{ $row['start'] ?? '' }}</span>
                    <span>～</span>
                    <span>{{ $row['end'] ?? '' }}</span>
                </td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td>
                    <span>{{ $displayMemo ?? '' }}</span>
                </td>
            </tr>
        </table>

        <div class="detail-actions">
            @if($isPending)
            <button type="submit" class="btn btn--small">承認</button>
            @else
            <p class="notice">承認済み</p>
            @endif
        </div>
    </form>
</div>
@endsection