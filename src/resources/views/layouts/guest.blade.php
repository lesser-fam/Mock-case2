<!DOCTYPE html>
<html lang="ja" class="bg-auth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/bases/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bases/common.css') }}">
    @yield('css')
</head>

<body class="bg-auth">
    <header class="header">
        <a href="{{ request()->routeIs('admin/*')
            ? route('admin.login')
            : route('login')
        }}">
            <img class="header__logo" src="{{ asset('images/logo.png') }}" alt="ロゴ">
        </a>
    </header>

    @yield('content')

</body>

</html>