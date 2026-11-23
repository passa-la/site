<?php

namespace App\Domain\Transit;

final readonly class Timetable
{
    private const int NEXT = 3;

    public function __construct(
        private Schedule $schedule,
    ) {}

    public function of(string $line, Leg $leg, Day $d, Moment $now): ?Table
    {
        if (! $leg->board) {
            return null;
        }

        $stop = $leg->board->stop->id;
        $end = $leg->fits() ? $leg->drop?->stop->id : null;
        $all = $this->schedule->at($line, $leg->dir, $stop, $d, $end);

        if ($d !== $now->day) {
            return new Table($stop, $end, $all);
        }

        $late = array_map(fn (Bus $b) => $b->shift(-Moment::DAY), $this->schedule->at($line, $leg->dir, $stop, $now->eve, $end));
        $next = array_values(array_filter([...$late, ...$all], fn (Bus $b) => $b->at >= $now->secs));
        usort($next, fn (Bus $a, Bus $b) => $a->at <=> $b->at);

        return new Table($stop, $end, $all, array_slice($next, 0, self::NEXT), $now->secs);
    }
}
