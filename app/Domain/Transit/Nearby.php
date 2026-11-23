<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Circle;

final readonly class Nearby
{
    public function __construct(
        private Lines $lines,
    ) {}

    public function of(Circle $c, Day $d): array
    {
        $stops = [];

        foreach ($this->lines->around($c, $d) as $s) {
            if (! $c->has($s->point)) {
                continue;
            }

            foreach ($s->lines as $id) {
                $stops[$id][] = $s;
            }
        }

        $all = array_map(
            fn (Line $l) => Pass::best($l, $stops[$l->id], $c->center),
            $this->lines->many(array_keys($stops)),
        );

        return Pass::sort(array_filter($all));
    }
}
