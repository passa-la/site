<?php

namespace App\Http\Api;

use App\Cases\Lookup;
use App\Http\Api\Json\Place;
use App\Http\Api\Requests\Query;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class Places
{
    public function __invoke(Query $r, Lookup $lookup): AnonymousResourceCollection
    {
        return Place::collection($lookup($r->text()));
    }
}
