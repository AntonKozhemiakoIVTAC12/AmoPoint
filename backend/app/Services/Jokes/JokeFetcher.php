<?php

namespace App\Services\Jokes;

use App\Models\Joke;
use App\Services\Jokes\Exceptions\JokeApiException;
use Illuminate\Support\Facades\Log;

/**
 * Бизнес-операция Sprint 1: «забрать анекдот и положить в БД».
 *
 * Контроллер/команда не должны знать ни про API, ни про схему таблицы —
 * только дёргать `fetchAndStore()` и обрабатывать результат.
 */
class JokeFetcher
{
    public const SOURCE = 'official-joke-api';

    public function __construct(private readonly JokeApiClient $api)
    {
    }

    /**
     * @throws JokeApiException
     */
    public function fetchAndStore(): Joke
    {
        $data = $this->api->fetchRandom();

        $joke = Joke::updateOrCreate(
            [
                'source' => self::SOURCE,
                'external_id' => isset($data['id']) ? (int) $data['id'] : null,
            ],
            [
                'type' => $data['type'] ?? null,
                'setup' => mb_substr((string) $data['setup'], 0, 1024),
                'punchline' => mb_substr((string) $data['punchline'], 0, 1024),
            ]
        );

        Log::info('joke stored', ['id' => $joke->id, 'external_id' => $joke->external_id]);

        return $joke;
    }
}
