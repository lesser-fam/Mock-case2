@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-list">
    <h1>勤怠一覧</h1>

    <div class="pager">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}">←前月</a>
        <span>{{ $baseMonth->format('Y/m') }}</span>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}">翌月→</a>
    </div>

    <div class="table">
        <div class="row head">
            <div>日付</div>
            <div>出勤</div>
            <div>退勤</div>
            <div>休憩</div>
            <div>合計</div>
            <div>詳細</div>
        </div>

        @foreach ($days as $row)
        @php
        $d = $row['date'];
        $a = $row['attendance'];

        $weekday = ['日','月','火','水','木','金','土'][$d->dayOfWeek];
        $dateLabel = $d->format('m/d') . "($weekday)";

        $start = $a?->work_start_at ? $a->work_start_at->format('H:i') : '';
        $end = $a?->work_end_at ? $a->work_end_at->format('H:i') : '';

        $breakMin = $row['breakMinutes'] ?? 0;
        $workMin = $row['workMinutes'];

        $breakLabel = $breakMin ? sprintf('%d:%02d', intdiv($breakMin, 60), $breakMin % 60) : '';
        $workLabel = is_null($workMin) ? '' : sprintf('%d:%02d', intdiv($workMin, 60), $workMin % 60);
        @endphp

        <div class="row">
            <div>{{ $dateLabel }}</div>
            <div>{{ $start }}</div>
            <div>{{ $end }}</div>
            <div>{{ $breakLabel }}</div>
            <div>{{ $workLabel }}</div>
            <div>
                <a class="btn btn--small" href="{{ route('attendance.detail', ['id' => $a->id]) }}">詳細</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection