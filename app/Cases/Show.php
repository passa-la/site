<?php

namespace App\Cases;

use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Detail;
use App\Domain\Transit\Lines;
use App\Domain\Transit\Moment;
use App\Domain\Transit\Plan;
use App\Domain\Transit\Timetable;

final readonly class Show
{
    public function __construct(
        private Lines $lines,
        private Timetable $timetable,
    ) {}

    public function __invoke(string $id, Day $day, Moment $now, ?Point $to = null, ?Point $from = null, ?string $stop = null, ?string $end = null): ?Detail
    {
        $line = $this->lines->find($id);

        if (! $line) {
            return null;
        }

        $ways = $this->lines->ways($id, $day);
        $plan = Plan::of($ways, $to, $from, $stop, $end);
        $tables = [];

        foreach ($plan->legs as $l) {
            $t = $this->timetable->of($id, $l, $day, $now);

            if ($t) {
                $tables[$l->dir] = $t;
            }
        }

        return new Detail($line, $day, $ways, $plan, $tables);
    }
}
