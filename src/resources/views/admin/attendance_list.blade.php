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

    <table class="admin-attendance-list__table">
        <thead>
            <tr class="admin-attendance-list__row admin-attendance-list__head">
                <th scope="col">名前</th>
                <th scope="col">出勤</th>
                <th scope="col">退勤</th>
                <th scope="col">休憩</th>
                <th scope="col">合計</th>
                <th scope="col">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
            @php
            $staff = $r['staff'];
            $a = $r['attendance'];
            @endphp
            <tr class="admin-attendance-list__row">
                <td>{{ $staff->name }}</td>
                <td>{{ $r['start'] }}</td>
                <td>{{ $r['end'] }}</td>
                <td>{{ $r['breakLabel'] }}</td>
                <td>{{ $r['workLabel'] }}</td>
                <td class="admin-attendance-list__detail">
                    @if ($a)
                    <a class="btn btn--list-detail" href="{{ route('admin.attendance.show', ['id' => $a->id]) }}">詳細</a>
                    @else
                    <span class="btn btn--list-detail is-disable">詳細</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection