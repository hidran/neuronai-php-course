<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Support chat')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font: 18px/1.5 system-ui, sans-serif;
            color: #1f2933; background: #f4f6f8; }
        header { display: flex; justify-content: space-between;
            align-items: center; padding: 14px 32px;
            color: #fff; background: #1f2933; }
        header a { color: #fff; text-decoration: none; }
        .brand { font-weight: 700; }
        nav { display: flex; gap: 24px; align-items: center; }
        nav form { margin: 0; }
        main { max-width: 760px; margin: 32px auto; padding: 0 24px; }
        h1 { margin: 0 0 20px; font-size: 30px; }
        label { display: block; margin: 12px 0 4px; font-weight: 600; }
        input, textarea { width: 100%; padding: 10px; font: inherit;
            border: 1px solid #9aa5b1; border-radius: 6px; }
        button { margin-top: 14px; padding: 10px 20px; font: inherit;
            color: #fff; background: #2563eb; border: 0;
            border-radius: 6px; cursor: pointer; }
        button.link { margin: 0; padding: 0; background: none;
            text-decoration: underline; }
        fieldset { margin: 0; padding: 0; border: 0; }
        fieldset:disabled label { color: #7b8794; }
        fieldset:disabled textarea { background: #eef1f4; }
        fieldset:disabled button { background: #7b8794;
            cursor: not-allowed; }
        .card { margin-top: 24px; padding: 20px; background: #fff;
            border: 1px solid #d9e2ec; border-radius: 10px; }
        .error { margin: 6px 0 0; color: #b42318; }
        .list, .transcript { margin: 0; padding: 0; list-style: none; }
        .list li { margin-bottom: 10px; padding: 14px 18px;
            background: #fff; border: 1px solid #d9e2ec;
            border-radius: 10px; }
        .list a { color: #2563eb; font-weight: 600; }
        .transcript li { width: fit-content; max-width: 80%;
            margin-bottom: 12px; padding: 12px 16px;
            border-radius: 12px; white-space: pre-wrap; }
        .transcript li::before { display: block; font-size: 13px;
            font-weight: 700; opacity: 0.7; }
        .transcript .user { margin-left: auto; color: #fff;
            background: #2563eb; }
        .transcript .user::before { content: "You"; }
        .transcript .assistant { background: #fff;
            border: 1px solid #d9e2ec; }
        .transcript .assistant::before { content: "Support"; }
        .list .empty, .transcript .empty { color: #52606d;
            background: none; border: 0; padding-left: 0; }
        button:disabled { background: #7b8794; cursor: wait; }
        .transcript .thinking { color: #52606d; font-style: italic; }
        .transcript .not-sent { background: #52606d; }
        .transcript .not-sent::before { content: "You, not sent"; }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="{{ url('/') }}">Support chat</a>
        @auth
            <nav>
                <a href="{{ route('conversations.index') }}">
                    Conversations
                </a>
                <span>Signed in as {{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link">Sign out</button>
                </form>
            </nav>
        @endauth
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
