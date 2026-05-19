@extends('layouts.app')
@section('title', 'Дашборд — AmoPoint')

@section('content')
<header>
    <h1>AmoPoint · статистика посещений</h1>
    <div>
        <span style="margin-right:14px;color:#94a3b8;font-size:13px">{{ auth()->user()?->email }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Выйти</button>
        </form>
    </div>
</header>
<main>
    <div class="grid grid-3" style="margin-bottom: 20px;">
        <div class="card kpi">Всего визитов <strong id="kpi-total">{{ $stats['totals']['visits'] }}</strong></div>
        <div class="card kpi">Уникальных <strong id="kpi-uniq">{{ $stats['totals']['uniques'] }}</strong></div>
        <div class="card kpi">За 24 часа <strong id="kpi-24">{{ $stats['totals']['last_24h'] }}</strong></div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>Уникальные посещения по часам (последние 24 ч)</h2>
            <canvas id="chart-hours" height="220"></canvas>
        </div>
        <div class="card">
            <h2>Города</h2>
            <canvas id="chart-cities" height="220"></canvas>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Устройства</h2>
        <canvas id="chart-devices" height="120"></canvas>
    </div>

    <p style="margin-top:24px;font-size:12px;color:#94a3b8">
        Данные обновляются при перезагрузке страницы. Источник: <code>GET /api/stats</code>.
    </p>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const stats = @json($stats);

    const palette = ['#38bdf8','#f472b6','#facc15','#34d399','#a78bfa','#fb7185','#60a5fa','#fbbf24','#4ade80','#c084fc','#f87171','#22d3ee'];
    const text = '#e2e8f0';
    const grid = 'rgba(148,163,184,.2)';

    Chart.defaults.color = text;
    Chart.defaults.borderColor = grid;

    new Chart(document.getElementById('chart-hours'), {
        type: 'bar',
        data: {
            labels: stats.hourly.map(h => h.label),
            datasets: [{
                label: 'Уникальные визиты',
                data: stats.hourly.map(h => h.uniques),
                backgroundColor: '#38bdf8',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { title: { display: true, text: 'время (час)' } },
                y: { beginAtZero: true, title: { display: true, text: 'кол-во уникальных' } }
            }
        }
    });

    new Chart(document.getElementById('chart-cities'), {
        type: 'pie',
        data: {
            labels: stats.cities.map(c => c.city),
            datasets: [{
                data: stats.cities.map(c => c.total),
                backgroundColor: stats.cities.map((_, i) => palette[i % palette.length]),
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'right' } } }
    });

    new Chart(document.getElementById('chart-devices'), {
        type: 'bar',
        data: {
            labels: stats.devices.map(d => d.device),
            datasets: [{
                label: 'Визиты',
                data: stats.devices.map(d => d.total),
                backgroundColor: '#a78bfa',
                borderRadius: 4,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
</script>
@endsection
