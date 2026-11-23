<?php

namespace Tests\Fakes;

use App\Domain\Transit\Day;
use App\Domain\Transit\Schedule as Port;

final class Schedule implements Port
{
    public array $asked = [];

    public function __construct(
        private array $buses,
    ) {}

    public function at(string $line, int $dir, string $stop, Day $d, ?string $end = null): array
    {
        $this->asked[] = [$stop, $d, $end];

        return $this->buses[$d->value] ?? [];
    }
}
