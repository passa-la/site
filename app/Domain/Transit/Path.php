<?php

namespace App\Domain\Transit;

final readonly class Path
{
    public function __construct(
        public string $line,
        public int $dir,
        public array $days,
        public array $points,
    ) {}
}
