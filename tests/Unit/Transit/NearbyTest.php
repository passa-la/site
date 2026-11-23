<?php

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Line;
use App\Domain\Transit\Nearby;
use App\Domain\Transit\Pass;
use App\Domain\Transit\Stop;
use Tests\Fakes\Lines;

beforeEach(function () {
    $this->from = new Point(-8.0, -34.85);
    $this->lines = [
        new Line('a', '100', 'Centro', 'X'),
        new Line('b', '200', 'Praia', 'X'),
        new Line('c', '050', 'Longe', 'X'),
    ];
    $this->stops = [
        new Stop('s1', '', new Point(-8.0005, -34.85), ['a']),
        new Stop('s2', '', new Point(-8.002, -34.85), ['a', 'b']),
        new Stop('s3', '', new Point(-8.05, -34.85), ['c']),
    ];
});

it('lists each line once at its closest stop, nearest first', function () {
    $all = (new Nearby(new Lines($this->lines, $this->stops)))->of(new Circle($this->from));

    expect(array_map(fn (Pass $p) => [$p->line->code, $p->stop->id], $all))->toBe([
        ['100', 's1'],
        ['200', 's2'],
    ])->and((int) round($all[0]->meters))->toBe(56);
});

it('breaks distance ties by line code', function () {
    $stops = [new Stop('s1', '', new Point(-8.0005, -34.85), ['b', 'a'])];
    $all = (new Nearby(new Lines($this->lines, $stops)))->of(new Circle($this->from));

    expect(array_map(fn (Pass $p) => $p->line->code, $all))->toBe(['100', '200']);
});

it('picks the best stop of a line for a point', function () {
    $p = Pass::best($this->lines[0], $this->stops, new Point(-8.05, -34.85));

    expect($p->stop->id)->toBe('s3');
});
