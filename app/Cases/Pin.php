<?php

namespace App\Cases;

use App\Domain\Geo\Geocoder;
use App\Domain\Geo\Place;
use App\Domain\Geo\Point;
use Throwable;

final readonly class Pin
{
    public const string NAME = 'Ponto no mapa';

    public function __construct(
        private Geocoder $geo,
    ) {}

    public function __invoke(Point $p): Place
    {
        try {
            $found = $this->geo->at($p);
        } catch (Throwable) {
            $found = null;
        }

        return new Place($found->name ?? self::NAME, $found->label ?? '', $p, $found->state ?? '');
    }
}
