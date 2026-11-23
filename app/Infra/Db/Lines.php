<?php

namespace App\Infra\Db;

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Line;
use App\Domain\Transit\Lines as Port;
use App\Domain\Transit\Path;
use App\Domain\Transit\Stop;
use Illuminate\Database\ConnectionInterface;

final readonly class Lines implements Port
{
    private const int SLACK = 10;

    private const string STOP = 'select s.id, s.name, st_y(s.geom::geometry) as lat, st_x(s.geom::geometry) as lon';

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function around(Circle $c): array
    {
        $rows = $this->db->select(
            self::STOP.', json_agg(ls.line_id) as lines
            from stops s
            join line_stop ls on ls.stop_id = s.id
            where st_dwithin(s.geom, st_point(?, ?, 4326)::geography, ?)
            group by s.id',
            [$c->center->lon, $c->center->lat, $c->meters + self::SLACK],
        );

        return array_map(fn (object $r) => $this->stop($r, json_decode($r->lines, true)), $rows);
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

    public function stops(string $id): array
    {
        $rows = $this->db->select(
            self::STOP.' from stops s join line_stop ls on ls.stop_id = s.id where ls.line_id = ? order by s.id',
            [$id],
        );

        return array_map(fn (object $r) => $this->stop($r), $rows);
    }

    public function paths(string $id): array
    {
        $rows = $this->db->select(
            'select dir, st_asgeojson(geom::geometry, 6) as geo from paths where line_id = ? order by dir',
            [$id],
        );

        return array_map(fn (object $r) => new Path(
            $id,
            (int) $r->dir,
            array_map(fn (array $c) => new Point($c[1], $c[0]), json_decode($r->geo, true)['coordinates']),
        ), $rows);
    }

    private function line(object $r): Line
    {
        return new Line($r->id, $r->code, $r->name, $r->agency);
    }

    private function stop(object $r, array $lines = []): Stop
    {
        return new Stop($r->id, $r->name, new Point((float) $r->lat, (float) $r->lon), $lines);
    }
}
