@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/attendance.css') }}">
@endsection

@section('content')
<div class="container--clock attendance">
    <div class="attendance__content">
        <div class="attendance__status">{{ $statusLabel }}</div>

        <div class="attendance__date">{{ $dateLabel }}</div>
        <div class="attendance__time">{{ $timeLabel }}</div>

        <div class="attendance__actions">
            @if ($status === 'outside')
            <form method="POST" action="{{ route('attendance.stamp.work_start') }}">
                @csrf
                <button class="btn btn--clock btn--black" type="submit">出勤</button>
            </form>
            @elseif ($status === 'working')
            <div class="attendance__actions-row">
                <form method="POST" action="{{ route('attendance.stamp.work_end') }}">
                    @csrf
                    <button class="btn btn--clock btn--black" type="submit">退勤</button>
                </form>
                <form method="POST" action="{{ route('attendance.stamp.break_start') }}">
                    @csrf
                    <button class="btn btn--clock btn--white" type="submit">休憩入</button>
                </form>
            </div>
            @elseif ($status === 'breaking')
            <form method="POST" action="{{ route('attendance.stamp.break_end') }}">
                @csrf
                <button class="btn btn--clock btn--white" type="submit">休憩戻</button>
            </form>
            @elseif ($status === 'finished')
            <p class="attendance__message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection