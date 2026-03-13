@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
<div class="container--narrow staff-list">
    <h1 class="staff-list__title">スタッフ一覧</h1>

    <table class="staff-list__table">
        <thead>
            <tr class="staff-list__table-row staff-list__table-head">
                <th scope="col">名前</th>
                <th scope="col">メールアドレス</th>
                <th scope="col">月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($staffs as $staff)
            <tr class="staff-list__table-row">
                <td class="staff-list__cell">{{ $staff->name }}</td>
                <td class="staff-list__cell staff-list__email" title="{{ $staff->email }}">{{ $staff->email }}</td>
                <td class="staff-list__cell">
                    <a class="btn btn--list-detail"
                        href="{{ route('admin.staff.month.index', ['id' => $staff->id]) }}">
                        詳細
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection