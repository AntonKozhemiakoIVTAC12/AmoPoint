@extends('layouts.app')
@section('title', 'Вход — AmoPoint')

@section('content')
<header>
    <h1>AmoPoint · статистика</h1>
</header>
<main>
    <form class="auth card" method="POST" action="{{ route('login.store') }}">
        @csrf
        <h2>Вход в дашборд</h2>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', 'admin@amopoint.local') }}" required autofocus>
        <label>Пароль</label>
        <input type="password" name="password" required>
        <button type="submit">Войти</button>
        <p style="margin-top:14px;font-size:12px;color:#94a3b8">
            По умолчанию: <code>admin@amopoint.local</code> / <code>secret123</code>
            (создаётся сидером <code>AdminUserSeeder</code>).
        </p>
    </form>
</main>
@endsection
