@extends('layouts.guest')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
@endsection

@section('content')
@include('shared.login_form', [
'heading' => 'ログイン',
'action' => route('login'),
'buttonLabel' => 'ログインする',
'linkUrl' => route('register'),
'linkLabel' => '会員登録はこちら',
])
@endsection