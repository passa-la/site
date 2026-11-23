<?php

namespace App\Domain\Transit;

final readonly class Pattern
{
    public function __construct(
        public int $id,
        public string $line,
        public int $dir,
        public array $stops,
    ) {}
}
