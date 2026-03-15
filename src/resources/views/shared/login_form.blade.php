<div class="container--form">
    <h1 class="form__heading">{{ $heading }}</h1>

    <form class="form" action="{{ $action }}" method="POST" novalidate>
        @csrf
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
        <input class="btn btn--form btn--black" type="submit" value="{{ $buttonLabel }}">
        @isset($linkUrl, $linkLabel)
        <a class="form__link" href="{{ $linkUrl }}">{{ $linkLabel }}</a>
        @endisset
    </form>
</div>