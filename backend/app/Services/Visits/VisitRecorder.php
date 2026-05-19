<?php

namespace App\Services\Visits;

use App\Models\Visit;
use App\Support\GeoIp;
use App\Support\UserAgentParser;
use Illuminate\Support\Str;

/**
 * Принимает DTO и сохраняет визит в БД, дозаполняя поля из UA и GeoIP.
 *
 * Контроллер ничего не знает про парсинг User-Agent и геолокацию —
 * вся «обогащающая» логика инкапсулирована здесь.
 */
class VisitRecorder
{
    public function __construct(private readonly GeoIp $geo)
    {
    }

    public function record(VisitData $data): Visit
    {
        $uaParts = UserAgentParser::parse($data->userAgent);

        $city = $data->city;
        $country = $data->country;

        if ($city === null || $country === null) {
            $geo = $this->geo->lookup($data->ip);
            $city ??= $geo['city'];
            $country ??= $geo['country'];
        }

        return Visit::create([
            'visitor_id' => $data->visitorId ?: (string) Str::uuid(),
            'ip' => $data->ip,
            'city' => $city,
            'country' => $country,
            'device' => $data->device ?: $uaParts['device'],
            'browser' => $data->browser ?: $uaParts['browser'],
            'os' => $data->os ?: $uaParts['os'],
            'url' => $data->url,
            'referrer' => $data->referrer,
            'user_agent' => $data->userAgent,
        ]);
    }
}
