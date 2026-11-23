<?php

use App\Domain\Geo\Point;
use App\Domain\Transit\Plan;
use App\Domain\Transit\Stop;
use App\Domain\Transit\Way;

beforeEach(function () {
    $a = new Stop('a', '', new Point(-8.000, -34.85));
    $b = new Stop('b', '', new Point(-8.010, -34.85));
    $c = new Stop('c', '', new Point(-8.020, -34.85));

    $this->ways = [new Way(0, [], [$a, $b, $c]), new Way(1, [], [$c, $b, $a])];
    $this->home = new Point(-8.0005, -34.85);
    $this->work = new Point(-8.0195, -34.85);
});

it('picks the direction that reaches the destination after boarding', function () {
    expect(Plan::of($this->ways, $this->work, $this->home, null)->best)->toBe(0)
        ->and(Plan::of($this->ways, $this->home, $this->work, null)->best)->toBe(1);
});

it('shows where to get off in each direction without a location', function () {
    $plan = Plan::of($this->ways, $this->work, null, null);

    expect($plan->best)->toBeNull()
        ->and(array_map(fn ($l) => [$l->dir, $l->drop->stop->id, $l->drop->seq, $l->board], $plan->legs))
        ->toBe([[0, 'c', 2, null], [1, 'c', 0, null]]);
});

it('boards at the stop the rider chose', function () {
    $plan = Plan::of($this->ways, $this->home, $this->work, 'b');

    expect($plan->best)->toBe(1)
        ->and($plan->legs[1]->board->stop->id)->toBe('b')
        ->and($plan->legs[1]->fits())->toBeTrue();
});

it('accepts any boarding on a single direction line', function () {
    $loop = [new Way(0, [], $this->ways[0]->stops)];

    expect(Plan::of($loop, $this->home, $this->work, null)->best)->toBe(0);
});

it('gets off at the stop the rider chose', function () {
    $plan = Plan::of($this->ways, null, null, 'a', 'c');

    expect($plan->best)->toBe(0)
        ->and($plan->legs[0]->drop->stop->id)->toBe('c')
        ->and($plan->legs[0]->fits())->toBeTrue()
        ->and($plan->legs[1]->drop->stop->id)->toBe('c')
        ->and($plan->legs[1]->fits())->toBeFalse();
});

it('never boards at the last stop of a direction', function () {
    $plan = Plan::of($this->ways, null, null, 'c');

    expect($plan->best)->toBe(1)
        ->and($plan->legs[0]->board)->toBeNull()
        ->and($plan->legs[1]->board->stop->id)->toBe('c');
});
