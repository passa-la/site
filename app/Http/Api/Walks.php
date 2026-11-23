<?php

namespace App\Http\Api;

use App\Cases\Walk;
use App\Http\Api\Json\Walk as Json;
use App\Http\Api\Requests\Trail;

final class Walks
{
    public function __invoke(Trail $r, Walk $walk): Json
    {
        return new Json($walk($r->origin() ?? abort(422), $r->point() ?? abort(422)));
    }
}
