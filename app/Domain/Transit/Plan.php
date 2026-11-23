<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Point;

final readonly class Plan
{
    public function __construct(
        public array $legs,
        public ?int $best,
    ) {}

    public static function of(array $ways, ?Point $to, ?Point $from, ?string $stop, ?string $end = null): self
    {
        $legs = array_map(function (Way $w) use ($to, $from, $stop, $end) {
            $board = $w->pick($stop, $from);

            return new Leg($w->dir, $board, $w->drop($end, $to, $board));
        }, $ways);

        return new self($legs, self::best($legs, $stop));
    }

    private static function best(array $legs, ?string $stop): ?int
    {
        $ok = array_filter($legs, fn (Leg $l) => $l->board !== null);

        if ($stop !== null) {
            $ok = array_filter($ok, fn (Leg $l) => $l->board?->stop->id === $stop);
        }

        if (! $ok) {
            return null;
        }

        if (count($legs) > 1) {
            $ok = array_filter($ok, fn (Leg $l) => $l->fits()) ?: $ok;
        }

        usort($ok, fn (Leg $a, Leg $b) => $a->cost() <=> $b->cost());

        return $ok[0]->dir;
    }
}
