<?php

namespace App\Domain\Transit;

use App\Domain\Geo\Point;

final readonly class Stop
{
    public function __construct(
        public string $id,
        public string $name,
        public Point $point,
        public array $lines = [],
    ) {}
}
