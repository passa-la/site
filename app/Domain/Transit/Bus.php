<?php

namespace App\Domain\Transit;

final readonly class Bus
{
    public function __construct(
        public int $at,
        public ?int $end = null,
    ) {}

    public function shift(int $secs): self
    {
        return new self($this->at + $secs, $this->end === null ? null : $this->end + $secs);
    }

    public function ride(): ?int
    {
        return $this->end === null ? null : (int) round(($this->end - $this->at) / 60);
    }
}
