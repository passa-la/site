<?php

namespace App\Infra\Gtfs;

use App\Domain\Geo\Point;
use App\Domain\Transit\Feed;
use App\Domain\Transit\Line;
use App\Domain\Transit\Network;
use App\Domain\Transit\Path;
use App\Domain\Transit\Stop;
use RuntimeException;
use ZipArchive;

final class Reader
{
    private const string BUS = '3';

    public function read(string $file, ?string $tag = null, ?string $today = null): Feed
    {
        $zip = new ZipArchive;

        if ($zip->open($file, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("Cannot open GTFS archive {$file}");
        }

        try {
            $csv = new Csv($zip);
            $live = $this->live($csv, $today ?? date('Ymd'));
            $lines = $this->lines($csv);
            [$trips, $shapes] = $this->trips($csv, $lines, $live);
            $links = $this->links($csv, $trips);
            $stops = $this->stops($csv, $links);
            $used = array_fill_keys(array_merge(...array_values($links ?: [[]])), true);

            return new Feed(
                $this->version($csv),
                $tag,
                new Network(
                    array_values(array_intersect_key($lines, $used)),
                    $stops,
                    $this->paths($csv, $shapes),
                ),
            );
        } finally {
            $zip->close();
        }
    }

    private function version(Csv $csv): string
    {
        foreach ($csv->rows('feed_info.txt', ['feed_version']) as $r) {
            if ($r['feed_version'] !== '') {
                return $r['feed_version'];
            }
        }

        return date('Y.m.d');
    }

    private function live(Csv $csv, string $today): array
    {
        $live = [];

        foreach ($csv->rows('calendar.txt', ['service_id', 'end_date']) as $r) {
            if ($r['end_date'] >= $today) {
                $live[$r['service_id']] = true;
            }
        }

        foreach ($csv->rows('calendar_dates.txt', ['service_id', 'date', 'exception_type']) as $r) {
            if ($r['exception_type'] === '1' && $r['date'] >= $today) {
                $live[$r['service_id']] = true;
            }
        }

        return $live;
    }

    private function lines(Csv $csv): array
    {
        $agencies = [];

        foreach ($csv->rows('agency.txt', ['agency_id', 'agency_name']) as $r) {
            $agencies[$r['agency_id']] = $r['agency_name'];
        }

        $lines = [];

        foreach ($csv->rows('routes.txt', ['route_id', 'agency_id', 'route_short_name', 'route_long_name', 'route_type']) as $r) {
            if ($r['route_type'] !== self::BUS) {
                continue;
            }

            $lines[$r['route_id']] = new Line(
                $r['route_id'],
                $r['route_short_name'] ?: $r['route_id'],
                $r['route_long_name'] ?: $r['route_short_name'],
                $agencies[$r['agency_id']] ?? $r['agency_id'],
            );
        }

        return $lines;
    }

    private function trips(Csv $csv, array $lines, array $live): array
    {
        $trips = [];
        $count = [];

        foreach ($csv->rows('trips.txt', ['route_id', 'service_id', 'trip_id', 'direction_id', 'shape_id']) as $r) {
            if (! isset($lines[$r['route_id']], $live[$r['service_id']])) {
                continue;
            }

            $trips[$r['trip_id']] = $r['route_id'];

            if ($r['shape_id'] !== '') {
                $key = $r['route_id']."\0".(int) $r['direction_id'];
                $count[$key][$r['shape_id']] = ($count[$key][$r['shape_id']] ?? 0) + 1;
            }
        }

        $shapes = [];

        foreach ($count as $key => $ids) {
            arsort($ids);
            [$line, $dir] = explode("\0", $key);
            $shapes[(string) array_key_first($ids)] = [$line, (int) $dir];
        }

        return [$trips, $shapes];
    }

    private function links(Csv $csv, array $trips): array
    {
        $links = [];

        foreach ($csv->rows('stop_times.txt', ['trip_id', 'stop_id']) as $r) {
            $line = $trips[$r['trip_id']] ?? null;

            if ($line !== null) {
                $links[$r['stop_id']][$line] = true;
            }
        }

        return array_map(fn (array $l) => array_map('strval', array_keys($l)), $links);
    }

    private function stops(Csv $csv, array $links): array
    {
        $stops = [];

        foreach ($csv->rows('stops.txt', ['stop_id', 'stop_name', 'stop_lat', 'stop_lon']) as $r) {
            if (! isset($links[$r['stop_id']])) {
                continue;
            }

            $stops[] = new Stop(
                $r['stop_id'],
                $this->name($r['stop_name'], $r['stop_id']),
                new Point((float) $r['stop_lat'], (float) $r['stop_lon']),
                $links[$r['stop_id']],
            );
        }

        return $stops;
    }

    private function name(string $name, string $id): string
    {
        $clean = trim((string) preg_replace('/\s*-\s*'.preg_quote($id, '/').'$/', '', trim($name)));

        return $clean === $id ? '' : $clean;
    }

    private function paths(Csv $csv, array $shapes): array
    {
        $pts = [];

        foreach ($csv->rows('shapes.txt', ['shape_id', 'shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence']) as $r) {
            if (isset($shapes[$r['shape_id']])) {
                $pts[$r['shape_id']][(int) $r['shape_pt_sequence']] = [(float) $r['shape_pt_lat'], (float) $r['shape_pt_lon']];
            }
        }

        $paths = [];

        foreach ($pts as $id => $seq) {
            ksort($seq);
            [$line, $dir] = $shapes[$id];
            $paths[] = new Path($line, $dir, array_map(fn (array $p) => new Point($p[0], $p[1]), array_values($seq)));
        }

        return $paths;
    }
}
