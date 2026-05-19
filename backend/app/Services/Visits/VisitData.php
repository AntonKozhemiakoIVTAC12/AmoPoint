<?php

namespace App\Services\Visits;

/**
 * Иммутабельный DTO для входящего трекинг-события.
 * Избавляет сервис от знаний о Request и упрощает unit-тесты.
 */
final class VisitData
{
    public function __construct(
        public readonly ?string $visitorId,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly ?string $url,
        public readonly ?string $referrer,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly ?string $device = null,
        public readonly ?string $browser = null,
        public readonly ?string $os = null,
    ) {
    }

    /**
     * @param  array<string,mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            visitorId: $input['visitor_id'] ?? null,
            ip: $input['ip'] ?? null,
            userAgent: $input['user_agent'] ?? null,
            url: $input['url'] ?? null,
            referrer: $input['referrer'] ?? null,
            city: $input['city'] ?? null,
            country: $input['country'] ?? null,
            device: $input['device'] ?? null,
            browser: $input['browser'] ?? null,
            os: $input['os'] ?? null,
        );
    }
}
