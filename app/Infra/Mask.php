<?php

namespace App\Infra;

use App\Domain\Transit\Day;

final class Mask
{
    private const array BITS = ['weekday' => 1, 'saturday' => 2, 'sunday' => 4];

    public static function bit(Day $d): int
    {
        return self::BITS[$d->value];
    }

    public static function of(array $days): int
    {
        return array_reduce($days, fn (int $m, Day $d) => $m | self::bit($d), 0);
    }

    public static function days(int $mask): array
    {
        return array_values(array_filter(Day::cases(), fn (Day $d) => ($mask & self::bit($d)) !== 0));
    }
}
