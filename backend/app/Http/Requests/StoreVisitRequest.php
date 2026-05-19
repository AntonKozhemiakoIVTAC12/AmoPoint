<?php

namespace App\Http\Requests;

use App\Services\Visits\VisitData;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_id' => ['nullable', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'city' => ['nullable', 'string', 'max:128'],
            'country' => ['nullable', 'string', 'max:128'],
            'device' => ['nullable', 'string', 'max:64'],
            'browser' => ['nullable', 'string', 'max:64'],
            'os' => ['nullable', 'string', 'max:64'],
            'ip' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function toVisitData(): VisitData
    {
        $validated = $this->validated();

        return VisitData::fromArray([
            ...$validated,
            'ip' => $validated['ip'] ?? $this->ip(),
            'user_agent' => $this->header('User-Agent'),
            'referrer' => $validated['referrer'] ?? $this->header('referer'),
        ]);
    }
}
