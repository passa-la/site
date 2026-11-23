<?php

namespace App\Domain\Transit;

use DateTimeImmutable;

final readonly class Moment
{
    public const int DAY = 86400;

    public function __construct(
        public Day $day,
        public Day $eve,
        public int $secs,
    ) {}

    public static function of(DateTimeImmutable $t): self
    {
        return new self(
            Day::of($t),
            Day::of($t->modify('-1 day')),
            $t->getTimestamp() - $t->setTime(0, 0)->getTimestamp(),
        );
    }
}
