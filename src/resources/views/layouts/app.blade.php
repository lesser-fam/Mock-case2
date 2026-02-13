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
        <a href="{{ auth()->user()->role === 'admin'
            ? route('admin.attendance.list')
            : route('attendance')
        }}">
            <img class="header__logo" src="{{ asset('images/logo.png') }}" alt="ロゴ">
        </a>

        @auth
        <ul class="header__nav">
            @if ($navRole === 'admin')
            <li><a href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
            <li><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
            <li><a href="{{ route('request.list') }}">申請一覧</a></li>

            @elseif ($navRole === 'user' && $navStatus === 'finished')
            <li><a href="{{ route('attendance.list') }}">今月の出勤一覧</a></li>
            <li><a href="{{ route('request.list') }}">申請一覧</a></li>

            @else
            <li><a href="{{ route('attendance') }}">勤怠</a></li>
            <li><a href="{{ route('attendance.list') }}">勤怠一覧</a></li>
            <li><a href="{{ route('request.list') }}">申請</a></li>
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

</body>

</html>