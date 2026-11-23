<?php

namespace App\Infra\Db;

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use App\Domain\Transit\Halt;
use App\Domain\Transit\Line;
use App\Domain\Transit\Lines as Port;
use App\Domain\Transit\Stop;
use App\Domain\Transit\Way;
use App\Infra\Mask;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final readonly class Lines implements Port
{
    private const int SLACK = 10;

    private const int LIMIT = 20;

    private const string STOP = 'select s.id, s.name, st_y(s.geom::geometry) as lat, st_x(s.geom::geometry) as lon';

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function around(Circle $c, Day $d): array
    {
        $rows = $this->db->select(
            self::STOP.', json_agg(distinct ls.line_id) as lines
            from stops s
            join line_stop ls on ls.stop_id = s.id
            where ls.days & ? > 0
            and st_dwithin(s.geom, st_point(?, ?, 4326)::geography, ?)
            group by s.id',
            [Mask::bit($d), $c->center->lon, $c->center->lat, $c->meters + self::SLACK],
        );

        return array_map(fn (object $r) => $this->stop($r, json_decode($r->lines, true)), $rows);
    }

    public function halts(Circle $c, Day $d): array
    {
        $rows = $this->db->select(
            self::STOP.', ls.line_id, ls.dir, ls.seq
            from stops s
            join line_stop ls on ls.stop_id = s.id
            where ls.days & ? > 0
            and st_dwithin(s.geom, st_point(?, ?, 4326)::geography, ?)',
            [Mask::bit($d), $c->center->lon, $c->center->lat, $c->meters + self::SLACK],
        );

        return array_map(fn (object $r) => new Halt($this->stop($r), $r->line_id, (int) $r->dir, (int) $r->seq), $rows);
    }

    public function many(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return $this->db->table('lines')
            ->whereIn('id', array_map('strval', $ids))
            ->get()
            ->map(fn (object $r) => $this->line($r))
            ->all();
    }

    public function find(string $id): ?Line
    {
        $r = $this->db->table('lines')->where('id', $id)->first();

        return $r ? $this->line($r) : null;
    }

    public function match(string $q): array
    {
        $t = self::term($q);

        if ($t === '') {
            return [];
        }

        return $this->db->table('lines')
            ->where('term', 'like', '%'.addcslashes($t, '%_\\').'%')
            ->orderByRaw("case when ltrim(code, '0') = ltrim(?, '0') then 0 when code like ? then 1 else 2 end", [$t, addcslashes($t, '%_\\').'%'])
            ->orderBy('code')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (object $r) => $this->line($r))
            ->all();
    }

    public static function term(string $s): string
    {
        return Str::of($s)->ascii()->lower()->squish()->toString();
    }

    public function ways(string $id, Day $d): array
    {
        $bit = Mask::bit($d);
        $stops = [];
        $points = [];

        $rows = $this->db->select(
            self::STOP.', ls.dir from line_stop ls join stops s on s.id = ls.stop_id
            where ls.line_id = ? and ls.days & ? > 0 order by ls.dir, ls.seq',
            [$id, $bit],
        );

        foreach ($rows as $r) {
            $stops[(int) $r->dir][] = $this->stop($r);
        }

        $paths = $this->db->select(
            'select dir, st_asgeojson(geom::geometry, 6) as geo from paths where line_id = ? and days & ? > 0 order by dir',
            [$id, $bit],
        );

        foreach ($paths as $r) {
            $points[(int) $r->dir] = array_map(
                fn (array $c) => new Point($c[1], $c[0]),
                json_decode($r->geo, true)['coordinates'],
            );
        }

        $dirs = array_unique([...array_keys($stops), ...array_keys($points)]);
        sort($dirs);

        return array_map(fn (int $dir) => new Way($dir, $points[$dir] ?? [], $stops[$dir] ?? []), $dirs);
    }

    private function line(object $r): Line
    {
        return new Line($r->id, $r->code, $r->name, $r->agency, Mask::days((int) $r->days));
    }

    private function stop(object $r, array $lines = []): Stop
    {
        return new Stop($r->id, $r->name, new Point((float) $r->lat, (float) $r->lon), $lines);
    }
}
