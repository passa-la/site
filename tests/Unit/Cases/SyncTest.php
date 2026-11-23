<?php

use App\Cases\Sync;
use App\Domain\Transit\Feed;
use App\Domain\Transit\Line;
use App\Domain\Transit\Network;
use Tests\Fakes\Source;
use Tests\Fakes\Store;

it('does nothing when the feed did not change', function () {
    $source = new Source(null);
    $store = new Store('"tag"');

    expect((new Sync($source, $store))())->toBeNull()
        ->and($source->asked)->toBe(['"tag"'])
        ->and($store->saved)->toBe([]);
});

it('saves the new network with its change', function () {
    $feed = new Feed('v2', '"new"', new Network([new Line('1', '100', 'A', 'X'), new Line('4', '400', 'D', 'X')], [], []));
    $store = new Store('"old"', ['100', '200']);

    $change = (new Sync(new Source($feed), $store))();

    expect($change->added)->toBe(['400'])
        ->and($change->removed)->toBe(['200'])
        ->and($store->saved[0][0])->toBe($feed);
});

it('ignores the stored tag when forced', function () {
    $source = new Source(null);

    (new Sync($source, new Store('"tag"')))(true);

    expect($source->asked)->toBe([null]);
});
