<?php

namespace App\Cases;

use App\Domain\Geo\Point;
use App\Domain\Transit\Detail;
use App\Domain\Transit\Lines;
use App\Domain\Transit\Pass;

final readonly class Show
{
    public function __construct(
        private Lines $lines,
    ) {}

    public function __invoke(string $id, ?Point $from = null): ?Detail
    {
        $line = $this->lines->find($id);

        if (! $line) {
            return null;
        }

        $stops = $this->lines->stops($id);

        return new Detail(
            $line,
            $this->lines->paths($id),
            $stops,
            $from ? Pass::best($line, $stops, $from) : null,
        );
    }
}
