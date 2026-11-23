<?php

use App\Domain\Transit\Change;

it('tells added and removed lines', function () {
    $c = Change::of('v2', ['100', '200', '300'], ['100', '300', '400']);

    expect($c->added)->toBe(['400'])
        ->and($c->removed)->toBe(['200'])
        ->and($c->total)->toBe(3)
        ->and($c->empty())->toBeFalse()
        ->and(Change::of('v3', ['1'], ['1'])->empty())->toBeTrue();
});
