<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Point;

final readonly class Pass
{
    public function __construct(
        public Line $line,
        public Stop $stop,
        public float $meters,
    ) {}

    public static function best(Line $line, array $stops, Point $from): ?self
    {
        $best = null;

        foreach ($stops as $s) {
            $m = $from->to($s->point);

            if (! $best || $m < $best->meters) {
                $best = new self($line, $s, $m);
            }
        }

        return $best;
    }

    public static function sort(array $all): array
    {
        usort($all, fn (self $a, self $b) => [$a->meters, $a->line->code] <=> [$b->meters, $b->line->code]);

        return $all;
    }
}
