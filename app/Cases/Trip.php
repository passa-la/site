<?php

namespace App\Cases;

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Direct;

final readonly class Trip
{
    public function __construct(
        private Direct $direct,
    ) {}

    public function __invoke(Point $from, Point $to, Day $d, int $meters = Circle::WALK): array
    {
        return $this->direct->between(new Circle($from, $meters), new Circle($to, $meters), $d);
    }
}
