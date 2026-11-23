<?php

namespace App\Cases;

use App\Domain\Transit\Change;
use App\Domain\Transit\Source;
use App\Domain\Transit\Store;

final readonly class Sync
{
    public function __construct(
        private Source $source,
        private Store $store,
    ) {}

    public function __invoke(bool $force = false): ?Change
    {
        $feed = $this->source->pull($force ? null : $this->store->tag());

        if (! $feed) {
            return null;
        }

        $change = Change::of($feed->version, $this->store->codes(), $feed->net->codes());
        $this->store->save($feed, $change);

        return $change;
    }
}
