<?php

namespace App\Http\Api\Json;

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
        ];
    }
}
