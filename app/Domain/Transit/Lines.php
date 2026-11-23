<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Circle;

interface Lines
{
    public function around(Circle $c): array;

    public function many(array $ids): array;

    public function find(string $id): ?Line;

    public function stops(string $id): array;

    public function paths(string $id): array;
}
