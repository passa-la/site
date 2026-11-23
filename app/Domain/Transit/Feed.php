<?php

namespace App\Domain\Transit;

final readonly class Feed
{
    public function __construct(
        public string $version,
        public ?string $tag,
        public Network $net,
    ) {}
}
