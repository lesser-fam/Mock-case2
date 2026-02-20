@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_list.css') }}">
@endsection

@section('content')
<div class="container--narrow attendance-list">
    <h1>勤怠一覧</h1>

    @include('shared.attendance_list_table')
</div>
@endsection