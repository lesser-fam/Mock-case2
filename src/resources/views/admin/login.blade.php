@extends('layouts.guest')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
@endsection

@section('content')

@include('shared.login_form', [
'heading' => '管理者ログイン',
'action' => route('admin.login.store'),
'buttonLabel' => '管理者ログインする',
])

@endsection