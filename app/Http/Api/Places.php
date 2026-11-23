<?php

namespace App\Http\Api;

use App\Cases\Lookup;
use App\Cases\Pin;
use App\Http\Api\Json\Place;
use App\Http\Api\Requests\Near;
use App\Http\Api\Requests\Query;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class Places
{
    public function index(Query $r, Lookup $lookup): AnonymousResourceCollection
    {
        return Place::collection($lookup($r->text()));
    }

    public function reverse(Near $r, Pin $pin): Place
    {
        return new Place($pin($r->point() ?? abort(422)));
    }
}
