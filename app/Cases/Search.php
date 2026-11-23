<?php

namespace App\Cases;

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Nearby;

final readonly class Search
{
    public function __construct(
        private Nearby $nearby,
    ) {}

    public function __invoke(Point $p, Day $d): array
    {
        return $this->nearby->of(new Circle($p), $d);
    }
}
