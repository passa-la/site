<?php

namespace App\Infra\Db;

use App\Domain\Geo\Point;
use App\Domain\Transit\Change;
use App\Domain\Transit\Feed;
use App\Domain\Transit\Line;
use App\Domain\Transit\Link;
use App\Domain\Transit\Path;
use App\Domain\Transit\Stop;
use App\Domain\Transit\Store as Port;
use App\Infra\Mask;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;

final readonly class Store implements Port
{
    private const int CHUNK = 500;

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function tag(): ?string
    {
        return $this->db->table('syncs')->latest('id')->value('tag');
    }

    public function when(): ?DateTimeImmutable
    {
        $at = $this->db->table('syncs')->latest('id')->value('created_at');

        return $at ? new DateTimeImmutable($at) : null;
    }

    public function codes(): array
    {
        $all = $this->db->table('lines')->pluck('code')->all();
        sort($all, SORT_NATURAL);

        return $all;
    }

    public function save(Feed $feed, Change $change): void
    {
        $this->db->transaction(function () use ($feed, $change) {
            foreach (['paths', 'line_stop', 'stops', 'lines'] as $t) {
                $this->db->table($t)->delete();
            }

            $this->lines($feed->net->lines);
            $this->stops($feed->net->stops);
            $this->links($feed->net->links);
            $this->paths($feed->net->paths);

            $this->db->table('syncs')->insert([
                'version' => $feed->version,
                'tag' => $feed->tag,
                'added' => json_encode($change->added),
                'removed' => json_encode($change->removed),
                'total' => $change->total,
                'created_at' => now(),
            ]);
        });
    }

    private function lines(array $lines): void
    {
        $rows = array_map(fn (Line $l) => [
            'id' => $l->id,
            'code' => $l->code,
            'name' => $l->name,
            'agency' => $l->agency,
            'days' => Mask::of($l->days),
            'term' => Lines::term($l->code.' '.$l->name),
        ], $lines);

        foreach (array_chunk($rows, self::CHUNK) as $c) {
            $this->db->table('lines')->insert($c);
        }
    }

    private function stops(array $stops): void
    {
        foreach (array_chunk($stops, self::CHUNK) as $c) {
            $this->insert('stops', ['id', 'name', 'geom'], '(?, ?, st_point(?, ?, 4326)::geography)', array_map(
                fn (Stop $s) => [$s->id, $s->name, $s->point->lon, $s->point->lat],
                $c,
            ));
        }
    }

    private function links(array $links): void
    {
        $rows = array_map(fn (Link $l) => [
            'line_id' => $l->line,
            'stop_id' => $l->stop,
            'dir' => $l->dir,
            'seq' => $l->seq,
            'days' => Mask::of($l->days),
        ], $links);

        foreach (array_chunk($rows, self::CHUNK) as $c) {
            $this->db->table('line_stop')->insert($c);
        }
    }

    private function paths(array $paths): void
    {
        foreach (array_chunk($paths, 20) as $c) {
            $this->insert('paths', ['line_id', 'dir', 'days', 'geom'], '(?, ?, ?, st_geogfromtext(?))', array_map(
                fn (Path $p) => [$p->line, $p->dir, Mask::of($p->days), $this->wkt($p->points)],
                $c,
            ));
        }
    }

    private function wkt(array $points): string
    {
        $pts = array_map(fn (Point $p) => round($p->lon, 6).' '.round($p->lat, 6), $points);

        if (count($pts) === 1) {
            $pts[] = $pts[0];
        }

        return 'SRID=4326;LINESTRING('.implode(',', $pts).')';
    }

    private function insert(string $table, array $cols, string $row, array $values): void
    {
        if (! $values) {
            return;
        }

        $sql = sprintf(
            'insert into %s (%s) values %s',
            $table,
            implode(', ', $cols),
            implode(', ', array_fill(0, count($values), $row)),
        );

        $this->db->insert($sql, array_merge(...$values));
    }
}
