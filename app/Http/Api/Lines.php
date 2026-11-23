<?php

namespace App\Http\Api;

use App\Cases\Search;
use App\Cases\Show;
use App\Domain\Geo\Circle;
use App\Http\Api\Json\Detail;
use App\Http\Api\Json\Pass;
use App\Http\Api\Requests\Near;
use App\Http\Api\Requests\Spot;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class Lines
{
    public function index(Near $r, Search $search): AnonymousResourceCollection
    {
        return Pass::collection($search($r->point()))
            ->additional(['meta' => ['radius' => Circle::WALK]]);
    }

    public function show(string $id, Spot $r, Show $show): Detail
    {
        return new Detail($show($id, $r->point()) ?? abort(404));
    }
}
