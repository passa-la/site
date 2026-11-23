<?php

namespace App\Http\Api\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Spot extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stop' => new Stop($this->resource->stop),
            'seq' => $this->resource->seq,
            'meters' => (int) round($this->resource->meters),
        ];
    }
}
