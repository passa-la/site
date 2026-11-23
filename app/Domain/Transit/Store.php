<?php

namespace App\Domain\Transit;

use DateTimeImmutable;

interface Store
{
    public function tag(): ?string;

    public function when(): ?DateTimeImmutable;

    public function codes(): array;

    public function save(Feed $feed, Change $change): void;
}
