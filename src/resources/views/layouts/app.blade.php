<!DOCTYPE html>
<html lang="ja" class="bg-app">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/bases/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bases/common.css') }}">
    @yield('css')
</head>

<body class="bg-app">
    <header class="header">

        @php
        if (!auth()->check()) {
        $homeUrl = route('login');
        } elseif (auth()->user()->role === 'admin') {
        $homeUrl = route('admin.attendance.daily.index');
        }else {
        $homeUrl = route('attendance.stamp.show');
        }
        @endphp

        <a href="{{ $homeUrl }}">
            <img class="header__logo" src="{{ asset('images/logo.png') }}" alt="ロゴ">
        </a>

        @auth
        <button
            type="button"
            class="header__menu-btn"
            aria-label="メニューを開く"
            aria-controls="header-nav"
            aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul id="header-nav" class="header__nav">
            @if ($navRole === 'admin')
            <li><a class="header__nav-item" href="{{ route('admin.attendance.daily.index') }}">勤怠一覧</a></li>
            <li><a class="header__nav-item" href="{{ route('admin.staff.index') }}">スタッフ一覧</a></li>
            <li><a class="header__nav-item" href="{{ route('request.index') }}">申請一覧</a></li>
            @elseif ($navRole === 'user' && $navStatus === 'finished')
            <li><a class="header__nav-item" href="{{ route('attendance.month.index') }}">今月の出勤一覧</a></li>
            <li><a class="header__nav-item" href="{{ route('request.index') }}">申請一覧</a></li>
            @else
            <li><a class="header__nav-item" href="{{ route('attendance.stamp.show') }}">勤怠</a></li>
            <li><a class="header__nav-item" href="{{ route('attendance.month.index') }}">勤怠一覧</a></li>
            <li><a class="header__nav-item" href="{{ route('request.index') }}">申請</a></li>
            @endif
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="from" value="{{ $navRole === 'admin' ? 'admin' : 'user' }}">
                    <button class="header__nav-item" type="submit">ログアウト</button>
                </form>
            </li>
        </ul>
        @endauth
    </header>

    @yield('content')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuButton = document.querySelector('.header__menu-btn');
            const nav = document.querySelector('.header__nav');

            if (!menuButton || !nav) return;

            menuButton.addEventListener('click', function() {
                nav.classList.toggle('is-open');

                const expanded = nav.classList.contains('is-open');
                menuButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        });
    </script>

</body>

</html>