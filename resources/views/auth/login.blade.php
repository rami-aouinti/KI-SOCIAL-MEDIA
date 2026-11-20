<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — KI Social Media</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f6fa;color:#172033;font-family:Inter,ui-sans-serif,system-ui,sans-serif}
        main{width:min(100%,420px);padding:32px;background:#fff;border:1px solid #e6eaf0;border-radius:18px;box-shadow:0 12px 40px #15213b0d}
        .eyebrow{font-size:12px;letter-spacing:.13em;text-transform:uppercase;color:#64748b;font-weight:700}
        h1{font-size:27px;letter-spacing:-.04em;margin:9px 0 8px}.muted{color:#64748b;font-size:14px;line-height:1.6}
        label{display:block;margin:19px 0 7px;font-size:13px;font-weight:700}
        input{width:100%;padding:12px 13px;border:1px solid #cbd5e1;border-radius:9px;font:inherit}
        input:focus{outline:2px solid #9db5f4;border-color:#5477c7}
        button{width:100%;margin-top:23px;padding:13px;border:0;border-radius:9px;background:#172033;color:#fff;font-size:14px;font-weight:700;cursor:pointer}
        .error{margin-top:7px;color:#b42318;font-size:12px}.remember{display:flex;align-items:center;gap:8px;margin-top:16px;font-size:13px;color:#475569}.remember input{width:auto}
    </style>
</head>
<body>
<main>
    <div class="eyebrow">KI Social Media</div>
    <h1>Welcome back</h1>
    <p class="muted">Sign in to access your editorial workspace.</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        @error('email')<div class="error">{{ $message }}</div>@enderror

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password')<div class="error">{{ $message }}</div>@enderror

        <label class="remember"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
