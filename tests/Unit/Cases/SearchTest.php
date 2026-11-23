<?php

use App\Cases\Search;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Line;
use App\Domain\Transit\Nearby;
use App\Domain\Transit\Pass;
use App\Domain\Transit\Stop;
use Tests\Fakes\Lines;

beforeEach(function () {
    $lines = [new Line('a', '100', 'Centro', 'X'), new Line('b', '200', 'Praia', 'X')];
    $stops = [
        new Stop('s1', '', new Point(-8.003, -34.85), ['a']),
        new Stop('s2', '', new Point(-8.030, -34.85), ['b']),
    ];

    $this->search = new Search(new Nearby(new Lines($lines, $stops)));
    $this->codes = fn ($found) => array_map(fn (Pass $p) => $p->line->code, $found->passes);
});

it('keeps to the chosen radius', function () {
    $found = ($this->search)(new Point(-8.0, -34.85), Day::Weekday, 1000);

    expect(($this->codes)($found))->toBe(['100'])
        ->and($found->wide)->toBeFalse();
});

it('shrinks the radius when asked', function () {
    $found = ($this->search)(new Point(-8.0, -34.85), Day::Weekday, 300);

    expect($found->wide)->toBeTrue()
        ->and(($this->codes)($found))->toBe(['100', '200']);
});

it('falls back to the nearest lines up to five kilometers', function () {
    $found = ($this->search)(new Point(-8.020, -34.85), Day::Weekday, 1000);

    expect($found->wide)->toBeTrue()
        ->and(($this->codes)($found))->toBe(['200', '100']);
});

it('gives up beyond five kilometers', function () {
    $found = ($this->search)(new Point(-8.2, -34.85), Day::Weekday, 1000);

    expect($found->passes)->toBe([])
        ->and($found->wide)->toBeTrue();
});
