<?php

namespace App\Domain\Transit;

final readonly class Change
{
    public function __construct(
        public string $version,
        public array $added,
        public array $removed,
        public int $total,
    ) {}

    public static function of(string $version, array $old, array $new): self
    {
        return new self(
            $version,
            array_values(array_diff($new, $old)),
            array_values(array_diff($old, $new)),
            count($new),
        );
    }

    public function empty(): bool
    {
        return ! $this->added && ! $this->removed;
    }
}
