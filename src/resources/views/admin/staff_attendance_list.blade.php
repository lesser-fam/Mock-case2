@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_list.css') }}">
@endsection

@section('content')
<div class="container--narrow attendance_list">
    <h1>{{ $staff->name }}さんの勤怠</h1>

    @if (session('status'))
    <p class="notice">{{ session('status') }}</p>
    @endif

    @include('shared.attendance_list_table')

    <div class="attendance-list__action">
        <a
            class="btn btn--black btn--csv"
            href="{{ route('admin.staff.month.csv', ['id' => $staff->id, 'month' => $baseMonth->format('Y-m')]) }}">
            CSV出力
        </a>
    </div>
</div>
@endsection