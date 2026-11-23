<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Point;

final readonly class Way
{
    public function __construct(
        public int $dir,
        public array $points,
        public array $stops,
    ) {}

    public function near(?Point $p): ?Spot
    {
        if (! $p) {
            return null;
        }

        $best = null;

        foreach ($this->stops as $i => $s) {
            $m = $p->to($s->point);

            if (! $best || $m < $best->meters) {
                $best = new Spot($s, $i, $m);
            }
        }

        return $best;
    }

    public function pick(?string $stop, ?Point $from): ?Spot
    {
        foreach ($this->stops as $i => $s) {
            if ($stop !== null && $s->id === $stop) {
                return new Spot($s, $i, $from ? $from->to($s->point) : 0.0);
            }
        }

        return $this->near($from);
    }
}
