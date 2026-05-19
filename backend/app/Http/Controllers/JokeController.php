<?php

namespace App\Http\Controllers;

use App\Http\Resources\JokeResource;
use App\Models\Joke;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JokeController extends Controller
{
    /**
     * GET /api/jokes — массив записей таблицы в формате JSON.
     *
     * Пагинация выполнена через `spatie/laravel-json-api-paginate`:
     *   `?page[number]=2&page[size]=20`
     * Ответ возвращается в формате JSON:API (data + meta + links).
     */
    public function index(): AnonymousResourceCollection
    {
        $jokes = Joke::query()
            ->orderByDesc('id')
            ->jsonPaginate();

        return JokeResource::collection($jokes);
    }
}
