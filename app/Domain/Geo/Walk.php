<?php

namespace App\Domain\Geo;

final readonly class Walk
{
    private const float PACE = 1.25;

    public function __construct(
        public array $points,
        public float $meters,
        public float $seconds,
        public bool $exact = true,
    ) {}

    public static function straight(Point $from, Point $to): self
    {
        $m = $from->to($to);

        return new self([$from, $to], $m, $m / self::PACE, false);
    }
}
