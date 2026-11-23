<?php

namespace App\Cases;

use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Detail;
use App\Domain\Transit\Lines;
use App\Domain\Transit\Plan;

final readonly class Show
{
    public function __construct(
        private Lines $lines,
    ) {}

    public function __invoke(string $id, Day $day, ?Point $to = null, ?Point $from = null, ?string $stop = null): ?Detail
    {
        $line = $this->lines->find($id);

        if (! $line) {
            return null;
        }

        $ways = $this->lines->ways($id, $day);

        return new Detail($line, $day, $ways, Plan::of($ways, $to, $from, $stop));
    }
}
