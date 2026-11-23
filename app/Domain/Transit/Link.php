<?php

namespace App\Domain\Transit;

final readonly class Link
{
    public function __construct(
        public string $line,
        public string $stop,
        public int $dir,
        public int $seq,
        public array $days,
    ) {}
}
