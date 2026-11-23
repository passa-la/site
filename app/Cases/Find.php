<?php

namespace App\Cases;

use App\Domain\Transit\Lines;

final readonly class Find
{
    public function __construct(
        private Lines $lines,
    ) {}

    public function __invoke(string $q): array
    {
        return $this->lines->match(trim($q));
    }
}
