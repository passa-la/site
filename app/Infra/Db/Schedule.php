<?php

namespace App\Infra\Db;

use App\Domain\Transit\Bus;
use App\Domain\Transit\Day;
use App\Domain\Transit\Schedule as Port;
use App\Infra\Mask;
use Illuminate\Database\ConnectionInterface;

final readonly class Schedule implements Port
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function at(string $line, int $dir, string $stop, Day $d, ?string $end = null): array
    {
        $join = $end === null
            ? 'left join pattern_stop b on false'
            : 'join pattern_stop b on b.pattern_id = p.id and b.stop_id = ? and b.pos > a.pos';

        $rows = $this->db->select(
            "select t.at, min(t.arr) as arr from (
                select r.times[a.pos] as at, r.times[b.pos] as arr
                from patterns p
                join pattern_stop a on a.pattern_id = p.id
                join runs r on r.pattern_id = p.id
                {$join}
                where p.line_id = ? and p.dir = ? and a.stop_id = ? and r.days & ? > 0 and a.pos < cardinality(r.times)
            ) t group by t.at order by t.at",
            [...($end === null ? [] : [$end]), $line, $dir, $stop, Mask::bit($d)],
        );

        return array_map(fn (object $r) => new Bus((int) $r->at, $r->arr === null ? null : (int) $r->arr), $rows);
    }
}
