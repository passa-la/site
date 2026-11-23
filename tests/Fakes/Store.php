<?php

namespace Tests\Fakes;

use App\Domain\Transit\Change;
use App\Domain\Transit\Feed;
use App\Domain\Transit\Store as Port;
use DateTimeImmutable;

final class Store implements Port
{
    public array $saved = [];

    public function __construct(
        private ?string $last = null,
        private array $now = [],
    ) {}

    public function tag(): ?string
    {
        return $this->last;
    }

    public function when(): ?DateTimeImmutable
    {
        return null;
    }

    public function codes(): array
    {
        return $this->now;
    }

    public function save(Feed $feed, Change $change): void
    {
        $this->saved[] = [$feed, $change];
    }
}
