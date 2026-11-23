<?php

namespace App\Cases;

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Found;
use App\Domain\Transit\Nearby;

final readonly class Search
{
    private const int SOME = 5;

    public function __construct(
        private Nearby $nearby,
    ) {}

    public function __invoke(Point $p, Day $d, int $meters = Circle::WALK): Found
    {
        $near = $this->nearby->of(new Circle($p, $meters), $d);

        if ($near) {
            return new Found($near);
        }

        return new Found(array_slice($this->nearby->of(new Circle($p, Circle::FAR), $d), 0, self::SOME), true);
    }
}
