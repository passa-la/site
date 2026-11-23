<?php

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Direct;
use App\Domain\Transit\Halt;
use App\Domain\Transit\Line;
use App\Domain\Transit\Ride;
use App\Domain\Transit\Stop;
use Tests\Fakes\Lines;

beforeEach(function () {
    $home = new Stop('h', '', new Point(-8.0005, -34.85));
    $near = new Stop('n', '', new Point(-8.0020, -34.85));
    $work = new Stop('w', '', new Point(-8.0300, -34.85));

    $this->lines = [new Line('a', '100', 'Ida', 'X'), new Line('b', '200', 'Volta', 'X'), new Line('c', '300', 'Longa', 'X')];
    $this->halts = [
        new Halt($home, 'a', 0, 0), new Halt($work, 'a', 0, 9),
        new Halt($work, 'b', 0, 0), new Halt($home, 'b', 0, 5),
        new Halt($work, 'b', 1, 5), new Halt($near, 'b', 1, 0),
        new Halt($home, 'c', 0, 0), new Halt($work, 'c', 0, 30),
    ];
    $this->from = new Circle(new Point(-8.0, -34.85));
    $this->to = new Circle(new Point(-8.0298, -34.85));
});

it('keeps lines that reach the destination after boarding, least walking first', function () {
    $rides = (new Direct(new Lines($this->lines, [], [], $this->halts)))->between($this->from, $this->to, Day::Weekday);

    expect(array_map(fn (Ride $r) => [$r->line->code, $r->dir, $r->board->stop->id, $r->drop->stop->id, $r->hops()], $rides))->toBe([
        ['100', 0, 'h', 'w', 9],
        ['300', 0, 'h', 'w', 30],
        ['200', 1, 'n', 'w', 5],
    ]);
});

it('finds nothing when no line serves both ends', function () {
    $halts = array_values(array_filter($this->halts, fn (Halt $h) => $h->stop->id !== 'w'));

    expect((new Direct(new Lines($this->lines, [], [], $halts)))->between($this->from, $this->to, Day::Weekday))->toBe([]);
});
