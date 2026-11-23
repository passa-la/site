<?php

namespace App\Domain\Transit;

final readonly class Detail
{
    public function __construct(
        public Line $line,
        public array $paths,
        public array $stops,
        public ?Pass $near = null,
    ) {}
}
