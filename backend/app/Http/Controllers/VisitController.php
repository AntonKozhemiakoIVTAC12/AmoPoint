<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitRequest;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use App\Services\Visits\VisitRecorder;
use App\Services\Visits\VisitStatsBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VisitController extends Controller
{
    /**
     * POST /api/track — публичный приём событий от клиента.
     */
    public function store(StoreVisitRequest $request, VisitRecorder $recorder): JsonResponse
    {
        $visit = $recorder->record($request->toVisitData());

        return response()->json([
            'ok' => true,
            'id' => $visit->id,
        ], 201);
    }

    /**
     * GET /api/visits — пагинированный список визитов (JSON:API).
     * Используется для отладки и просмотра сырых данных.
     */
    public function index(): AnonymousResourceCollection
    {
        $visits = Visit::query()
            ->orderByDesc('id')
            ->jsonPaginate();

        return VisitResource::collection($visits);
    }

    /**
     * GET /dashboard/stats.json — агрегаты для графиков (защищено auth).
     */
    public function stats(VisitStatsBuilder $stats): JsonResponse
    {
        return response()->json($stats->build());
    }
}
