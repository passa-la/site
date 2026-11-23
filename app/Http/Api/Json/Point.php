<?php

namespace App\Http\Api\Json;

use App\Domain\Geo\Point as Model;

final class Point
{
    private const int PRECISION = 6;

    public static function pair(Model $p): array
    {
        return [round($p->lon, self::PRECISION), round($p->lat, self::PRECISION)];
    }

    public static function of(Model $p): array
    {
        return ['lat' => round($p->lat, self::PRECISION), 'lon' => round($p->lon, self::PRECISION)];
    }
}
