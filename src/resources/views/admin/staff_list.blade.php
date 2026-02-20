@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
<div class="container--narrow staff-list">
    <h1 class="staff-list__title">スタッフ一覧</h1>

    <div class="staff-list__table">
        <div class="staff-list__table-row staff-list__table-head">
            <div>名前</div>
            <div>メールアドレス</div>
            <div>月次勤怠</div>
        </div>

        @foreach ($staffs as $staff)
        <div class="staff-list__table-row">
            <div class="staff-list__cell">{{ $staff->name }}</div>
            <div class="staff-list__cell">{{ $staff->email }}</div>
            <div class="staff-list__cell">
                <a class="btn btn--list-detail"
                    href="{{ route('admin.staff.attendances', ['id' => $staff->id]) }}">
                    詳細
                </a>
            </div>
        </div>
        @endforeach
    </div>



</div>
@endsection