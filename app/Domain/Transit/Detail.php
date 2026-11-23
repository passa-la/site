<?php

namespace App\Domain\Transit;

final readonly class Detail
{
    public function __construct(
        public Line $line,
        public Day $day,
        public array $ways,
        public Plan $plan,
        public array $tables = [],
    ) {}
}
