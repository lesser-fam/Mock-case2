@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
<div class="container--narrow admin-attendance-list">
    <h1 class="admin-attendance-list__title">
        {{ $baseDate->format('Y年n月j日') }}の勤怠
    </h1>

    <div class="admin-attendance-list__pager">
        <a href="{{ route('admin.attendance.daily.index', ['date' => $prevDate]) }}">←前日</a>

        <span class="admin-attendance-list__pager-center">
            <form class="pager-picker" action="{{ route('admin.attendance.daily.index') }}" method="GET">
                <input
                    id="admin-date-picker"
                    class="pager-picker__input"
                    type="date"
                    name="date"
                    value="{{ $baseDate->format('Y-m-d') }}"
                    aria-label="日付を選択"
                    onchange="this.form.submit()">
                <button
                    class="pager-picker__btn"
                    type="button"
                    aria-label="日付を選択"
                    onclick="document.getElementById('admin-date-picker').showPicker?.();
                            document.getElementById('admin-date-picker').focus();
                            document.getElementById('admin-date-picker').click();">
                    📆
                </button>
            </form>
            <span class="admin-attendance-list__pager-label">{{ $baseDate->format('Y/m/d') }}</span>
        </span>

        <a href="{{ route('admin.attendance.daily.index', ['date' => $nextDate]) }}">翌日→</a>
    </div>

    <div class="admin-attendance-list__table">
        <div class="admin-attendance-list__row admin-attendance-list__head">
            <div>名前</div>
            <div>出勤</div>
            <div>退勤</div>
            <div>休憩</div>
            <div>合計</div>
            <div>詳細</div>
        </div>

        @foreach ($rows as $r)
        @php
        $staff = $r['staff'];
        $a = $r['attendance'];
        @endphp
        <div class="admin-attendance-list__row">
            <div>{{ $staff->name }}</div>
            <div>{{ $r['start'] }}</div>
            <div>{{ $r['end'] }}</div>
            <div>{{ $r['breakLabel'] }}</div>
            <div>{{ $r['workLabel'] }}</div>
            <div class="admin-attendance-list__detail">
                <a class="btn btn--list-detail" href="{{ route('admin.attendance.show', ['id' => $a->id]) }}">詳細</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection