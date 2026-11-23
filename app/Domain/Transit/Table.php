<?php

namespace App\Domain\Transit;

final readonly class Table
{
    public function __construct(
        public string $stop,
        public ?string $end,
        public array $all,
        public ?array $next = null,
        public int $now = 0,
    ) {}

    public function wait(Bus $b): int
    {
        return intdiv($b->at - $this->now, 60);
    }

    public function ride(): ?int
    {
        $all = array_values(array_filter(array_map(fn (Bus $b) => $b->ride(), $this->all), fn (?int $m) => $m !== null));

        if (! $all) {
            return null;
        }

        sort($all);

        return $all[intdiv(count($all), 2)];
    }
}
