<?php

use App\Domain\Geo\Point;
use App\Domain\Transit\Bus;
use App\Domain\Transit\Day;
use App\Domain\Transit\Leg;
use App\Domain\Transit\Moment;
use App\Domain\Transit\Spot;
use App\Domain\Transit\Stop;
use App\Domain\Transit\Timetable;
use Tests\Fakes\Schedule;

beforeEach(function () {
    $this->a = new Spot(new Stop('a', '', new Point(-8.0, -34.85)), 0, 0.0);
    $this->b = new Spot(new Stop('b', '', new Point(-8.01, -34.85)), 3, 0.0);
    $this->late = new Schedule([
        'weekday' => [new Bus(18000, 18600), new Bus(21600, 22500), new Bus(88200, 88800)],
        'saturday' => [new Bus(20000)],
    ]);
});

it('reads the clock as a service day', function () {
    $m = Moment::of(new DateTimeImmutable('2026-11-28 00:30', new DateTimeZone('America/Recife')));

    expect($m->day)->toBe(Day::Saturday)
        ->and($m->eve)->toBe(Day::Weekday)
        ->and($m->secs)->toBe(1800);
});

it('lists the next buses at the boarding stop', function () {
    $t = (new Timetable($this->late))->of('100', new Leg(0, $this->a, null), Day::Weekday, new Moment(Day::Weekday, Day::Weekday, 19000));

    expect($t->stop)->toBe('a')
        ->and($t->end)->toBeNull()
        ->and(array_map(fn (Bus $b) => $b->at, $t->next))->toBe([21600, 88200])
        ->and($t->wait($t->next[0]))->toBe(43)
        ->and(count($t->all))->toBe(3);
});

it('counts last night buses that run after midnight', function () {
    $t = (new Timetable($this->late))->of('100', new Leg(0, $this->a, null), Day::Saturday, new Moment(Day::Saturday, Day::Weekday, 1000));

    expect(array_map(fn (Bus $b) => $b->at, $t->next))->toBe([1800, 20000])
        ->and(array_map(fn (Bus $b) => $b->at, $t->all))->toBe([20000]);
});

it('shows only the table on other days', function () {
    $t = (new Timetable($this->late))->of('100', new Leg(0, $this->a, null), Day::Weekday, new Moment(Day::Sunday, Day::Saturday, 0));

    expect($t->next)->toBeNull()
        ->and(count($t->all))->toBe(3);
});

it('asks when the bus gets to the drop stop only after boarding', function () {
    $s = $this->late;
    $tt = new Timetable($s);

    $t = $tt->of('100', new Leg(0, $this->a, $this->b), Day::Weekday, new Moment(Day::Sunday, Day::Saturday, 0));
    $tt->of('100', new Leg(0, $this->b, $this->a), Day::Weekday, new Moment(Day::Sunday, Day::Saturday, 0));

    expect($s->asked)->toBe([['a', Day::Weekday, 'b'], ['b', Day::Weekday, null]])
        ->and($t->end)->toBe('b')
        ->and($t->ride())->toBe(10);
});

it('needs a boarding stop', function () {
    expect((new Timetable($this->late))->of('100', new Leg(0, null, $this->b), Day::Weekday, new Moment(Day::Weekday, Day::Weekday, 0)))->toBeNull();
});
