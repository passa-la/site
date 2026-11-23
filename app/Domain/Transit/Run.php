<?php

namespace App\Domain\Transit;

final readonly class Run
{
    public function __construct(
        public int $pattern,
        public array $days,
        public array $times,
    ) {}
}
