<?php

namespace App\Domain\Geo;

interface Walker
{
    public function walk(Point $from, Point $to): Walk;
}
