<?php

namespace App\Domain\Geo;

interface Geocoder
{
    public function find(string $q, Region $r): array;
}
