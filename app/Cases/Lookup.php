<?php

namespace App\Cases;

use App\Domain\Geo\Geocoder;
use App\Domain\Geo\Place;
use App\Domain\Geo\Region;

final readonly class Lookup
{
    public function __construct(
        private Geocoder $geo,
        private Region $region,
    ) {}

    public function __invoke(string $q): array
    {
        $all = $this->geo->find(trim($q), $this->region);

        return array_values(array_filter($all, fn (Place $p) => $this->region->has($p)));
    }
}
