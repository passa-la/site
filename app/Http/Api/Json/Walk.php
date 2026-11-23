<?php

namespace App\Http\Api\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Walk extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => array_map(Point::pair(...), $this->resource->points),
            ],
            'meters' => (int) round($this->resource->meters),
            'minutes' => max(1, (int) round($this->resource->seconds / 60)),
            'exact' => $this->resource->exact,
        ];
    }
}
