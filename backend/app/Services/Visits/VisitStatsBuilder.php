<?php

namespace App\Services\Visits;

use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Сборщик статистики для дашборда. Считает то, что нужно для графиков:
 *   - почасовые уникальные за последние 24 часа,
 *   - топ-городов,
 *   - распределение по устройствам,
 *   - сводные числа.
 *
 * Запросы написаны под SQLite (strftime). Если потребуется MySQL/PG —
 * нужно подменить функцию в `groupedByHour()`.
 */
class VisitStatsBuilder
{
    private const HOURS_WINDOW = 24;
    private const CITY_TOP = 12;

    /**
     * @return array<string,mixed>
     */
    public function build(): array
    {
        $now = CarbonImmutable::now();
        $since = $now->subHours(self::HOURS_WINDOW - 1)->startOfHour();

        return [
            'generated_at' => $now->toIso8601String(),
            'totals' => $this->totals($now),
            'hourly' => $this->hourly($since),
            'cities' => $this->topCities(),
            'devices' => $this->devices(),
        ];
    }

    /**
     * @return array<string,int>
     */
    private function totals(CarbonImmutable $now): array
    {
        return [
            'visits' => (int) Visit::count(),
            'uniques' => (int) Visit::query()->distinct('visitor_id')->count('visitor_id'),
            'last_24h' => (int) Visit::query()->where('created_at', '>=', $now->subDay())->count(),
        ];
    }

    /**
     * @return array<int, array{hour:string,label:string,uniques:int,total:int}>
     */
    private function hourly(CarbonImmutable $since): array
    {
        $rows = $this->groupedByHour($since)->get()->keyBy('bucket');

        $buckets = [];
        for ($i = 0; $i < self::HOURS_WINDOW; $i++) {
            $stamp = $since->addHours($i)->format('Y-m-d H:00');
            $row = $rows->get($stamp);
            $buckets[] = [
                'hour' => $stamp,
                'label' => substr($stamp, 11, 5),
                'uniques' => $row ? (int) $row->uniques : 0,
                'total' => $row ? (int) $row->total : 0,
            ];
        }

        return $buckets;
    }

    private function groupedByHour(CarbonImmutable $since): Builder
    {
        return Visit::query()
            ->selectRaw("strftime('%Y-%m-%d %H:00', created_at) as bucket, COUNT(DISTINCT visitor_id) as uniques, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket');
    }

    /**
     * @return array<int, array{city:string,total:int}>
     */
    private function topCities(): array
    {
        return Visit::query()
            ->selectRaw('COALESCE(NULLIF(city, ""), ?) as city, COUNT(*) as total', ['Unknown'])
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(self::CITY_TOP)
            ->get()
            ->map(fn ($r) => ['city' => $r->city, 'total' => (int) $r->total])
            ->all();
    }

    /**
     * @return array<int, array{device:string,total:int}>
     */
    private function devices(): array
    {
        return Visit::query()
            ->selectRaw('COALESCE(NULLIF(device, ""), ?) as device, COUNT(*) as total', ['unknown'])
            ->groupBy('device')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['device' => $r->device, 'total' => (int) $r->total])
            ->all();
    }
}
