<?php

namespace App\Http\Api\Json;

use App\Domain\Transit\Leg;
use App\Domain\Transit\Way;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Detail extends JsonResource
{
    public function toArray(Request $request): array
    {
        $legs = [];

        foreach ($this->resource->plan->legs as $l) {
            $legs[$l->dir] = $l;
        }

        return [
            'line' => new Line($this->resource->line),
            'day' => $this->resource->day->value,
            'runs' => $this->resource->ways !== [],
            'best' => $this->resource->plan->best,
            'ways' => array_map(fn (Way $w) => $this->way($w, $legs[$w->dir] ?? null), $this->resource->ways),
        ];
    }

    private function way(Way $w, ?Leg $l): array
    {
        return [
            'dir' => $w->dir,
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => array_map(Point::pair(...), $w->points),
            ],
            'stops' => Stop::collection($w->stops),
            'board' => $l?->board ? new Spot($l->board) : null,
            'drop' => $l?->drop ? new Spot($l->drop) : null,
            'fits' => $l?->fits() ?? false,
        ];
    }
}
