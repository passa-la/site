<?php

namespace App\Domain\Transit;

final readonly class Leg
{
    public function __construct(
        public int $dir,
        public ?Spot $board,
        public ?Spot $drop,
    ) {}

    public function fits(): bool
    {
        return $this->board && $this->drop && $this->board->seq < $this->drop->seq;
    }

    public function cost(): float
    {
        return ($this->board->meters ?? 0) + ($this->drop->meters ?? 0);
    }
}
