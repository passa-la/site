<?php

namespace Tests\Fakes;

use App\Domain\Transit\Feed;
use App\Domain\Transit\Source as Port;

final class Source implements Port
{
    public array $asked = [];

    public function __construct(
        private ?Feed $feed,
    ) {}

    public function pull(?string $tag): ?Feed
    {
        $this->asked[] = $tag;

        return $this->feed;
    }
}
