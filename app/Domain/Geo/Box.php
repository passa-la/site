<?php

namespace App\Domain\Geo;

final readonly class Box
{
    public function __construct(
        public Point $min,
        public Point $max,
    ) {}

    public static function of(array $v): self
    {
        return new self(new Point($v[0], $v[1]), new Point($v[2], $v[3]));
    }

    public function has(Point $p): bool
    {
        return $p->lat >= $this->min->lat && $p->lat <= $this->max->lat
            && $p->lon >= $this->min->lon && $p->lon <= $this->max->lon;
    }
}
