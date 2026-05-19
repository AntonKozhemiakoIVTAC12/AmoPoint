<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Visit
 */
class VisitResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'visitor_id' => $this->visitor_id,
            'ip' => $this->ip,
            'city' => $this->city,
            'country' => $this->country,
            'device' => $this->device,
            'browser' => $this->browser,
            'os' => $this->os,
            'url' => $this->url,
            'referrer' => $this->referrer,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
