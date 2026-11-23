<?php

namespace App\Domain\Transit;

final readonly class Spot
{
    public function __construct(
        public Stop $stop,
        public int $seq,
        public float $meters,
    ) {}
}
