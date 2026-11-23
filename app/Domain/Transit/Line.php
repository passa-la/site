<?php

namespace App\Domain\Transit;

final readonly class Line
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $agency,
    ) {}
}
