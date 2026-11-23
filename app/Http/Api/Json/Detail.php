<?php

namespace App\Http\Api\Json;

use App\Domain\Transit\Path;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Detail extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'line' => new Line($this->resource->line),
            'paths' => array_map(fn (Path $p) => [
                'dir' => $p->dir,
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => array_map(Point::pair(...), $p->points),
                ],
            ], $this->resource->paths),
            'stops' => Stop::collection($this->resource->stops),
            'near' => $this->resource->near ? new Pass($this->resource->near) : null,
        ];
    }
}
