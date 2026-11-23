<?php

namespace App\Http\Api;

use App\Cases\Trip;
use App\Http\Api\Json\Ride;
use App\Http\Api\Requests\Trail;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class Rides
{
    public function __invoke(Trail $r, Trip $trip): AnonymousResourceCollection
    {
        $day = $r->day();
        $radius = $r->radius();
        $rides = $trip($r->origin() ?? abort(422), $r->point() ?? abort(422), $day, $radius);

        return Ride::collection($rides)->additional(['meta' => ['radius' => $radius, 'day' => $day->value]]);
    }
}
