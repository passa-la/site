<?php

namespace App\Cases;

use App\Domain\Geo\Point;
use App\Domain\Geo\Walk as Route;
use App\Domain\Geo\Walker;
use Throwable;

final readonly class Walk
{
    public function __construct(
        private Walker $walker,
    ) {}

    public function __invoke(Point $from, Point $to): Route
    {
        try {
            return $this->walker->walk($from, $to);
        } catch (Throwable) {
            return Route::straight($from, $to);
        }
    }
}
