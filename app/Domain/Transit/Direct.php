<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Circle;

final readonly class Direct
{
    public function __construct(
        private Lines $lines,
    ) {}

    public function between(Circle $from, Circle $to, Day $d): array
    {
        $boards = $this->group($from, $d);
        $drops = $this->group($to, $d);
        $best = [];

        foreach (array_intersect_key($boards, $drops) as $key => $starts) {
            [$line, $dir] = explode("\0", (string) $key);
            $pair = $this->pair($starts, $drops[$key]);

            if ($pair && (! isset($best[$line]) || $this->cost($pair) < $this->cost($best[$line]))) {
                $best[$line] = [...$pair, (int) $dir];
            }
        }

        $rides = [];

        foreach ($this->lines->many(array_keys($best)) as $l) {
            [$board, $drop, $dir] = $best[$l->id];
            $rides[] = new Ride($l, $dir, $board, $drop);
        }

        usort($rides, fn (Ride $a, Ride $b) => [$a->walk(), $a->hops(), $a->line->code] <=> [$b->walk(), $b->hops(), $b->line->code]);

        return $rides;
    }

    private function group(Circle $c, Day $d): array
    {
        $out = [];

        foreach ($this->lines->halts($c, $d) as $h) {
            $m = $c->center->to($h->stop->point);

            if ($m <= $c->meters) {
                $out[$h->line."\0".$h->dir][] = new Spot($h->stop, $h->seq, $m);
            }
        }

        return $out;
    }

    private function pair(array $starts, array $ends): ?array
    {
        $best = null;

        foreach ($ends as $e) {
            foreach ($starts as $s) {
                if ($s->seq < $e->seq && (! $best || $this->cost([$s, $e]) < $this->cost($best))) {
                    $best = [$s, $e];
                }
            }
        }

        return $best;
    }

    private function cost(array $pair): array
    {
        return [$pair[0]->meters + $pair[1]->meters, $pair[1]->seq - $pair[0]->seq];
    }
}
