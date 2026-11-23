<?php

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;

it('measures distance in meters', function () {
    $a = new Point(-8.0, -34.85);

    expect($a->to(new Point(-8.0, -34.85)))->toBe(0.0)
        ->and($a->to(new Point(-8.009, -34.85)))->toEqualWithDelta(1000.8, 1.0);
});

it('rejects coordinates out of range', function () {
    new Point(91, 0);
})->throws(InvalidArgumentException::class);

it('walks one kilometer by default', function () {
    $c = new Circle(new Point(-8.0, -34.85));

    expect($c->meters)->toBe(1000)
        ->and($c->has(new Point(-8.008, -34.85)))->toBeTrue()
        ->and($c->has(new Point(-8.01, -34.85)))->toBeFalse();
});
