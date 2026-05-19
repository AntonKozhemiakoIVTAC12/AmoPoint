<?php

namespace App\Services\Jokes;

use App\Services\Jokes\Exceptions\JokeApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Тонкий клиент поверх HTTP-фасада.
 * Цель — изолировать вызов внешнего API и сделать его легко мокабельным
 * (через `Http::fake()` или подмену сервиса в контейнере).
 */
class JokeApiClient
{
    public function __construct(
        private readonly string $url,
        private readonly int $timeout = 10,
        private readonly int $retries = 2,
    ) {
    }

    /**
     * @return array{id?: int, type?: string, setup: string, punchline: string}
     *
     * @throws JokeApiException
     */
    public function fetchRandom(): array
    {
        try {
            $response = $this->client()->get($this->url);
        } catch (Throwable $e) {
            throw new JokeApiException("Network error: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new JokeApiException("API responded with HTTP {$response->status()}");
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['setup'], $data['punchline'])) {
            throw new JokeApiException('Unexpected response shape');
        }

        return $data;
    }

    private function client(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->retry($this->retries, 200)
            ->acceptJson();
    }
}
