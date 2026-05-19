<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AmoPoint')</title>
    <style>
        :root { --bg:#0f172a; --panel:#1e293b; --text:#e2e8f0; --muted:#94a3b8; --accent:#38bdf8; --danger:#f87171; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background:var(--bg); color:var(--text); }
        header { background:var(--panel); padding: 16px 24px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 1px 0 rgba(255,255,255,.05); }
        header h1 { font-size: 18px; margin:0; }
        header a, header form button { color:var(--text); text-decoration:none; font-size:14px; }
        header form { display:inline; margin:0; }
        header form button { background:transparent; border:1px solid var(--muted); padding:6px 12px; border-radius:6px; cursor:pointer; }
        main { padding: 24px; max-width: 1200px; margin: 0 auto; }
        .card { background:var(--panel); border-radius: 12px; padding: 20px; box-shadow: 0 1px 0 rgba(255,255,255,.04); }
        .grid { display: grid; gap: 20px; }
        .grid-3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
        @media (max-width: 800px) { .grid-3, .grid-2 { grid-template-columns: 1fr; } }
        .kpi { font-size: 14px; color: var(--muted); }
        .kpi strong { display:block; font-size: 32px; color: var(--text); margin-top: 4px; }
        .card h2 { margin-top:0; font-size: 16px; color: var(--text); }
        form.auth { max-width: 380px; margin: 80px auto; }
        form.auth label { display:block; font-size: 13px; margin-bottom: 6px; color: var(--muted); }
        form.auth input[type=email], form.auth input[type=password] { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #334155; background:#0f172a; color:var(--text); margin-bottom: 14px; }
        form.auth button { width:100%; padding: 10px; background: var(--accent); color: #0f172a; border: 0; border-radius:8px; font-weight: 600; cursor: pointer; }
        .error { color: var(--danger); font-size: 13px; margin-bottom: 10px; }
        canvas { background: #0f172a; border-radius: 8px; padding: 8px; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
