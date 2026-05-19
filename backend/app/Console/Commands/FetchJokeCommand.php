<?php

namespace App\Console\Commands;

use App\Services\Jokes\Exceptions\JokeApiException;
use App\Services\Jokes\JokeFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 1 — консольная команда `jokes:fetch`.
 *
 * Только адаптер CLI → сервис. Никакой бизнес-логики тут больше нет.
 */
class FetchJokeCommand extends Command
{
    protected $signature = 'jokes:fetch';

    protected $description = 'Fetch a random joke from external API and store it in DB';

    public function handle(JokeFetcher $fetcher): int
    {
        try {
            $joke = $fetcher->fetchAndStore();
        } catch (JokeApiException $e) {
            Log::warning('jokes:fetch failed', ['e' => $e->getMessage()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Stored joke #{$joke->id} (external_id={$joke->external_id}).");

        return self::SUCCESS;
    }
}
