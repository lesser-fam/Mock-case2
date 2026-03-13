@extends('layouts.guest')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
@endsection

@section('content')
<div class="container--form">
    <h1 class="form__heading">会員登録</h1>
    <form class="form" action="{{ route('register') }}" method="POST" novalidate>
        @csrf
        <div class="form__group">
            <label class="form__label" for="name">ユーザー名</label>
            <input class="form__input" type="text" name="name" id="name" value="{{ old('name') }}">
            @error('name')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input class="form__input" type="email" name="email" id="email" value="{{ old('email') }}">
            @error('email')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input class="form__input" type="password" name="password" id="password">
            @error('password')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label class="form__label" for="password_confirmation">パスワード確認</label>
            <input class="form__input" type="password" name="password_confirmation" id="password_confirmation">
            @error('password_confirmation')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <input class="btn btn--form btn--black" type="submit" value="登録する">
        <a class="form__link" href="{{ route('login') }}">ログインはこちら</a>
    </form>
</div>
@endsection