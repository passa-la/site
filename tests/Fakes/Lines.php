<?php

namespace Tests\Fakes;

use App\Domain\Geo\Circle;
use App\Domain\Transit\Day;
use App\Domain\Transit\Line;
use App\Domain\Transit\Lines as Port;

final class Lines implements Port
{
    public function __construct(
        private array $lines,
        private array $stops,
        private array $ways = [],
    ) {}

    public function around(Circle $c, Day $d): array
    {
        return $this->stops;
    }

    public function many(array $ids): array
    {
        return array_values(array_filter($this->lines, fn (Line $l) => in_array($l->id, $ids, true)));
    }

    public function find(string $id): ?Line
    {
        return $this->many([$id])[0] ?? null;
    }

    public function ways(string $id, Day $d): array
    {
        return $this->ways;
    }
}
