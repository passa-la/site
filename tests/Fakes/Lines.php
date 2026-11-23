<?php

namespace Tests\Fakes;

use App\Domain\Geo\Circle;
use App\Domain\Transit\Line;
use App\Domain\Transit\Lines as Port;
use App\Domain\Transit\Stop;

final class Lines implements Port
{
    public function __construct(
        private array $lines,
        private array $stops,
    ) {}

    public function around(Circle $c): array
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

    public function stops(string $id): array
    {
        return array_values(array_filter($this->stops, fn (Stop $s) => in_array($id, $s->lines, true)));
    }

    public function paths(string $id): array
    {
        return [];
    }
}
