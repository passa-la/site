<?php

namespace App\Http\Api\Json;

use App\Domain\Transit\Bus;
use App\Domain\Transit\Moment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class Table extends JsonResource
{
    public function toArray(Request $request): array
    {
        $t = $this->resource;

        return [
            'stop' => $t->stop,
            'end' => $t->end,
            'ride' => $t->ride(),
            'next' => $t->next === null ? null : array_map(fn (Bus $b) => [
                'at' => self::clock($b->at),
                'in' => $t->wait($b),
                'end' => $b->end === null ? null : self::clock($b->end),
            ], $t->next),
            'all' => array_values(array_unique(array_map(fn (Bus $b) => self::clock($b->at), $t->all))),
        ];
    }

    private static function clock(int $secs): string
    {
        $s = $secs % Moment::DAY;

        return sprintf('%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60));
    }
}
