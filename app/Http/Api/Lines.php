<?php

namespace App\Http\Api;

use App\Cases\Find;
use App\Cases\Search;
use App\Cases\Show;
use App\Domain\Transit\Moment;
use App\Http\Api\Json\Detail;
use App\Http\Api\Json\Line;
use App\Http\Api\Json\Pass;
use App\Http\Api\Requests\Near;
use App\Http\Api\Requests\Spot;
use App\Http\Api\Requests\Term;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class Lines
{
    public function index(Near $r, Search $search): AnonymousResourceCollection
    {
        $day = $r->day();
        $radius = $r->radius();
        $found = $search($r->point() ?? abort(422), $day, $radius);

        return Pass::collection($found->passes)
            ->additional(['meta' => ['radius' => $radius, 'day' => $day->value, 'wide' => $found->wide]]);
    }

    public function search(Term $r, Find $find): AnonymousResourceCollection
    {
        return Line::collection($find($r->text()));
    }

    public function show(string $id, Spot $r, Show $show): Detail
    {
        $detail = $show(
            $id,
            $r->day(),
            Moment::of(now()->toDateTimeImmutable()),
            $r->point(),
            $r->origin(),
            $r->string('stop')->toString() ?: null,
            $r->string('end')->toString() ?: null,
        );

        return new Detail($detail ?? abort(404));
    }
}
