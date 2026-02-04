@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
@endsection

@section('content')
<div class="container">
    <h1>勤怠</h1>
    <p>{{ auth()->user()->name }} さん</p>
</div>
@endsection