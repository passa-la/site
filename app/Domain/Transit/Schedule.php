<?php

namespace App\Domain\Transit;

interface Schedule
{
    public function at(string $line, int $dir, string $stop, Day $d, ?string $end = null): array;
}
