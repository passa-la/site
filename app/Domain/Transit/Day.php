<?php

namespace App\Domain\Transit;

use DateTimeInterface;

enum Day: string
{
    case Weekday = 'weekday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public static function of(DateTimeInterface $d): self
    {
        return match ((int) $d->format('N')) {
            6 => self::Saturday,
            7 => self::Sunday,
            default => self::Weekday,
        };
    }
}
