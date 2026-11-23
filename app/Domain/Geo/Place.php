<?php

namespace App\Domain\Geo;

final readonly class Place
{
    public function __construct(
        public string $name,
        public string $label,
        public Point $point,
        public string $state = '',
    ) {}
}
