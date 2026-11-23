<?php

namespace App\Domain\Transit;

final readonly class Ride
{
    public function __construct(
        public Line $line,
        public int $dir,
        public Spot $board,
        public Spot $drop,
    ) {}

    public function walk(): float
    {
        return $this->board->meters + $this->drop->meters;
    }

    public function hops(): int
    {
        return $this->drop->seq - $this->board->seq;
    }
}
