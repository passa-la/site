<?php

namespace App\Domain\Transit;

final readonly class Halt
{
    public function __construct(
        public Stop $stop,
        public string $line,
        public int $dir,
        public int $seq,
    ) {}
}
