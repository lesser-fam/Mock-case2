@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance">
    <div class="attendance__card">
        <div class="attendance__status">
            @switch($status)
            @case('outside') 勤務外 @break
            @case('working') 出勤中 @break
            @case('breaking') 休憩中 @break
            @case('finished') 退勤済 @break
            @default 勤務外
            @endswitch
        </div>

        <div class="attendance__date">{{ $dateLabel }}</div>
        <div class="attendance__time">{{ $timeLabel }}</div>

        <div class="attendance__actions">
            @if ($status === 'outside')
                <form method="POST" action="{{ route('attendance.work.start') }}">
                    @csrf
                    <button class="btn" type="submit">出勤</button>
                </form>

            @elseif ($status === 'working')
                <div class="actions-row">
                    <form method="POST" action="{{ route('attendance.work.end') }}">
                        @csrf
                        <button class="btn" type="submit">退勤</button>
                    </form>

                    <form method="POST" action="{{ route('attendance.break.start') }}">
                        @csrf
                        <button class="btn" type="submit">休憩入</button>
                    </form>
                </div>

            @elseif ($status === 'breaking')
                <form method="POST" action="{{ route('attendance.break.end') }}">
                    @csrf
                    <button class="btn" type="submit">休憩戻</button>
                </form>

            @elseif ($status === 'finished')
                <p class="attendance__message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection