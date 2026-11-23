<?php

namespace App\Domain\Geo;

use InvalidArgumentException;

final readonly class Point
{
    private const int EARTH = 6371000;

    public function __construct(
        public float $lat,
        public float $lon,
    ) {
        if (abs($lat) > 90 || abs($lon) > 180) {
            throw new InvalidArgumentException("Invalid point {$lat},{$lon}");
        }
    }

    public function to(self $p): float
    {
        $a = deg2rad($this->lat);
        $b = deg2rad($p->lat);
        $h = sin(($b - $a) / 2) ** 2 + cos($a) * cos($b) * sin(deg2rad($p->lon - $this->lon) / 2) ** 2;

        return 2 * self::EARTH * asin(min(1, sqrt($h)));
    }
}
