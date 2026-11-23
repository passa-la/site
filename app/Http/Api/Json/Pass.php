<?php

namespace App\Http\Api\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Pass extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'line' => new Line($this->resource->line),
            'stop' => new Stop($this->resource->stop),
            'meters' => (int) round($this->resource->meters),
        ];
    }
}
