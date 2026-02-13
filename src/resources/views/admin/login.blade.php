@extends('layouts.guest')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
@endsection

@section('content')
<div class="container--form">
    <h1 class="form__heading">管理者ログイン</h1>
    <form class="form" action="{{ route('admin.login.store') }}" method="POST" novalidate>
        @csrf
        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input class="form__input" type="email" name="email" id="email" value="{{ old('email') }}">
            <p class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </p>
        </div>
        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input class="form__input" type="password" name="password" id="password">
            <p class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </p>
        </div>
        <input class="btn btn--form btn--black" type="submit" value="管理者ログインする">
    </form>
</div>
@endsection