<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Circle;

interface Lines
{
    public function around(Circle $c, Day $d): array;

    public function many(array $ids): array;

    public function find(string $id): ?Line;

    public function match(string $q): array;

    public function ways(string $id, Day $d): array;
}
