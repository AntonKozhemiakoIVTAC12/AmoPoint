@extends('layouts.app')
@section('title', 'Tracker demo')

@section('content')
<header>
    <h1>Tracker demo</h1>
    <a href="{{ route('dashboard') }}" style="font-size:14px">→ Дашборд</a>
</header>
<main>
    <div class="card">
        <h2>Демо-страница для проверки трекера</h2>
        <p>Эта страница сама себя считает посещаемой: при загрузке выполняется <code>tracker.js</code>,
        который шлёт POST <code>/api/track</code>. Откройте DevTools → Network, чтобы убедиться.</p>
        <p>После двух-трёх перезагрузок зайдите на <a href="{{ route('dashboard') }}">/dashboard</a>
        (логин <code>admin@amopoint.local</code> / <code>secret123</code>).</p>
        <button id="ping">Симулировать ещё один визит вручную</button>
        <pre id="log" style="background:#0f172a;padding:12px;border-radius:8px;margin-top:14px;overflow:auto;font-size:12px;color:#94a3b8"></pre>
    </div>
</main>
<script src="{{ asset('tracker.js') }}"
        data-endpoint="{{ url('/api/track') }}"
        data-auto="true"></script>
<script>
    document.getElementById('ping').addEventListener('click', () => {
        window.AmoPointTracker?.track().then(r => {
            const log = document.getElementById('log');
            log.textContent = JSON.stringify(r, null, 2) + "\n" + log.textContent;
        });
    });
</script>
@endsection
