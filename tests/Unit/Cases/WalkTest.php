<?php

use App\Cases\Walk;
use App\Domain\Geo\Point;
use App\Domain\Geo\Walk as Route;
use App\Domain\Geo\Walker;

it('falls back to a straight line when routing fails', function () {
    $walker = new class implements Walker
    {
        public function walk(Point $from, Point $to): Route
        {
            throw new RuntimeException('down');
        }
    };

    $r = (new Walk($walker))(new Point(-8.0, -34.85), new Point(-8.009, -34.85));

    expect($r->exact)->toBeFalse()
        ->and($r->points)->toHaveCount(2)
        ->and((int) round($r->meters))->toBe(1001)
        ->and((int) round($r->seconds))->toBe(801);
});
