<?php

namespace App\Http\Api\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Place extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'label' => $this->resource->label,
            ...Point::of($this->resource->point),
        ];
    }
}
