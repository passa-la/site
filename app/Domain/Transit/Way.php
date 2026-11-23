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

    public function near(?Point $p, bool $end = true): ?Spot
    {
        if (! $p) {
            return null;
        }

        $best = null;

        foreach ($this->stops as $i => $s) {
            if (! $end && ! $this->boards($i)) {
                continue;
            }

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
            if ($stop !== null && $s->id === $stop && $this->boards($i)) {
                return new Spot($s, $i, $from ? $from->to($s->point) : 0.0);
            }
        }

        return $this->near($from, false);
    }

    private function boards(int $i): bool
    {
        return $i === 0 || $i < count($this->stops) - 1;
    }

    public function drop(?string $end, ?Point $to, ?Spot $board): ?Spot
    {
        if ($end === null) {
            return $this->near($to);
        }

        $hit = $this->at($end, $board->seq ?? -1) ?? $this->at($end);

        return $hit ? new Spot($hit->stop, $hit->seq, $to ? $to->to($hit->stop->point) : 0.0) : null;
    }

    private function at(string $id, int $after = -1): ?Spot
    {
        foreach ($this->stops as $i => $s) {
            if ($s->id === $id && $i > $after) {
                return new Spot($s, $i, 0.0);
            }
        }

        return null;
    }
}
