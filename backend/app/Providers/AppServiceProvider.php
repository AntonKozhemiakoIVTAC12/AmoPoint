<?php

namespace App\Providers;

use App\Services\Jokes\JokeApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JokeApiClient::class, function ($app) {
            $config = $app['config']->get('services.jokes', []);

            return new JokeApiClient(
                url: $config['url'] ?? 'https://official-joke-api.appspot.com/random_joke',
                timeout: (int) ($config['timeout'] ?? 10),
                retries: (int) ($config['retries'] ?? 2),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
