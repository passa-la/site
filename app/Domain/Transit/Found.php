<?php

namespace App\Domain\Transit;

final readonly class Found
{
    public function __construct(
        public array $passes,
        public bool $wide = false,
    ) {}
}
