<?php

namespace App\Infra\Gtfs;

use App\Domain\Transit\Pattern;
use App\Domain\Transit\Run;
use App\Infra\Mask;

final class Book
{
    private array $ids = [];

    private array $days = [];

    private array $patterns = [];

    private array $runs = [];

    public function add(string $line, int $dir, int $mask, array $stops, array $times): void
    {
        $id = $this->ids[$line."\0".$dir."\0".implode("\0", $stops)] ??= count($this->ids) + 1;
        $this->patterns[$id] ??= new Pattern($id, $line, $dir, $stops);
        $this->runs[] = new Run($id, $this->days[$mask] ??= Mask::days($mask), $times);
    }

    public function patterns(array $lines): array
    {
        return array_values(array_filter($this->patterns, fn (Pattern $p) => isset($lines[$p->line])));
    }

    public function runs(array $lines): array
    {
        return array_values(array_filter($this->runs, fn (Run $r) => isset($lines[$this->patterns[$r->pattern]->line])));
    }
}
