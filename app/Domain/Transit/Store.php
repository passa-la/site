<?php

namespace App\Domain\Transit;

interface Store
{
    public function tag(): ?string;

    public function codes(): array;

    public function save(Feed $feed, Change $change): void;
}
