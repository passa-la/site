<?php

namespace App\Domain\Geo;

final readonly class Region
{
    public function __construct(
        public Box $box,
        public Point $hub,
        public string $state,
    ) {}

    public function has(Place $p): bool
    {
        return $this->box->has($p->point) && ($p->state === '' || $p->state === $this->state);
    }
}
