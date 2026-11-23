<?php

namespace App\Domain\Transit;

final readonly class Network
{
    public function __construct(
        public array $lines,
        public array $stops,
        public array $paths,
    ) {}

    public function codes(): array
    {
        $all = array_map(fn (Line $l) => $l->code, $this->lines);
        sort($all, SORT_NATURAL);

        return $all;
    }
}
