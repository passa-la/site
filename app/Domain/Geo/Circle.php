<?php

namespace App\Domain\Geo;

final readonly class Circle
{
    public const int MIN = 100;

    public const int WALK = 1000;

    public const int FAR = 5000;

    public function __construct(
        public Point $center,
        public int $meters = self::WALK,
    ) {}

    public function has(Point $p): bool
    {
        return $this->center->to($p) <= $this->meters;
    }
}
