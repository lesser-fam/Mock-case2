<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/bases/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bases/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <a href="">
            <img class="header__logo" src="{{ asset('images/logo.png') }}" alt="ロゴ">
        </a>

        @auth
        <ul class="header__nav">
            @if (Route::has('attendance'))
            <li><a href="{{ route('attendance') }}">勤怠</a></li>
            @endif

            @if (Route::has('attendance.list'))
            <li><a href="{{ route('attendance.list') }}">勤怠一覧</a></li>
            @endif

            @if (Route::has('request.list'))
            <li><a href="{{ route('request.list') }}">申請</a></li>
            @endif

            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="header__nav-item" type="submit">ログアウト</button>
                </form>
            </li>
        </ul>
        @endauth
    </header>

    @yield('content')

</body>

</html>