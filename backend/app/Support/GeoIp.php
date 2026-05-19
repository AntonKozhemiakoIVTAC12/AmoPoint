<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

/**
 * Тонкая обёртка над ip-api.com.
 * Результаты кешируются на час. Принципиально fail-silent — гео не должно ронять трекинг.
 */
class GeoIp
{
    public const PROVIDER_URL = 'http://ip-api.com/json/{ip}?fields=status,country,city,query';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
        private readonly int $timeout = 3,
        private readonly int $cacheTtlSec = 3600,
    ) {
    }

    /**
     * @return array{city: ?string, country: ?string}
     */
    public function lookup(?string $ip): array
    {
        $empty = ['city' => null, 'country' => null];

        if (! $ip || $this->isPrivate($ip)) {
            return $empty;
        }

        return $this->cache->remember(
            "geoip:{$ip}",
            $this->cacheTtlSec,
            fn () => $this->request($ip) ?? $empty
        );
    }

    /**
     * @return array{city: ?string, country: ?string}|null
     */
    private function request(string $ip): ?array
    {
        try {
            $url = str_replace('{ip}', $ip, self::PROVIDER_URL);
            $response = $this->http->timeout($this->timeout)->acceptJson()->get($url);
            if (! $response->successful()) {
                return null;
            }
            $payload = $response->json();
            if (($payload['status'] ?? null) !== 'success') {
                return null;
            }
            return [
                'city' => $payload['city'] ?? null,
                'country' => $payload['country'] ?? null,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function isPrivate(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
