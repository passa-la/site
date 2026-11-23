<?php

namespace App\Http\Api\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Ride extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'line' => new Line($this->resource->line),
            'dir' => $this->resource->dir,
            'board' => new Spot($this->resource->board),
            'drop' => new Spot($this->resource->drop),
            'walk' => (int) round($this->resource->walk()),
            'hops' => $this->resource->hops(),
        ];
    }
}
