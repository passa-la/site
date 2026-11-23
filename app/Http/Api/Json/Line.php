<?php

namespace App\Http\Api\Json;

use App\Domain\Transit\Day;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Line extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'agency' => $this->resource->agency,
            'days' => array_map(fn (Day $d) => $d->value, $this->resource->days),
        ];
    }
}
