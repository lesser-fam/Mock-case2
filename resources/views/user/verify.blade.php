@extends('layouts.guest')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/verify.css') }}">
@endsection

@section('content')
<div class="verify">
    <p class="verify__message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>
    <div class="verify__action">
        <a class="btn verify__button" href="http://localhost:8025" target="_blank">
            認証はこちらから
        </a>
    </div>
    <form class="verify__resend-form" action="{{ route('verification.resend') }}" method="POST">
        @csrf
        <button class="verify__resend-link" type="submit">
            認証メールを再送する
        </button>
    </form>
    @if (session('resent'))
    <p class="verify__resend-message">
        認証メールを再送信しました。
    </p>
    @endif
</div>
@endsection