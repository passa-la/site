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
        $day = $r->day();

        return Pass::collection($search($r->point() ?? abort(422), $day))
            ->additional(['meta' => ['radius' => Circle::WALK, 'day' => $day->value]]);
    }

    public function show(string $id, Spot $r, Show $show): Detail
    {
        $detail = $show($id, $r->day(), $r->point(), $r->origin(), $r->string('stop')->toString() ?: null);

        return new Detail($detail ?? abort(404));
    }
}
