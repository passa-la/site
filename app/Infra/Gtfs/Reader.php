<?php

namespace App\Infra\Gtfs;

use App\Domain\Geo\Point;
use App\Domain\Transit\Feed;
use App\Domain\Transit\Line;
use App\Domain\Transit\Link;
use App\Domain\Transit\Network;
use App\Domain\Transit\Path;
use App\Domain\Transit\Stop;
use App\Infra\Mask;
use DateTimeImmutable;
use Generator;
use RuntimeException;
use ZipArchive;

final class Reader
{
    private const string BUS = '3';

    private const array BITS = [1, 2, 4];

    public function read(string $file, ?string $tag = null, ?string $today = null): Feed
    {
        $zip = new ZipArchive;

        if ($zip->open($file, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("Cannot open GTFS archive {$file}");
        }

        try {
            $csv = new Csv($zip);
            $live = $this->live($csv, $today ?? date('Ymd'));
            $routes = $this->routes($csv);
            $best = $this->best($csv, $routes, $live);
            $reps = $this->reps($csv, $routes, $live, $best);
            $links = $this->links($this->seqs($csv, $reps), $reps);

            return new Feed(
                $this->version($csv),
                $tag,
                new Network(
                    $this->lines($routes, $links),
                    $this->stops($csv, $links),
                    $links,
                    $this->paths($csv, $best),
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
        $cols = ['service_id', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'end_date'];

        foreach ($csv->rows('calendar.txt', $cols) as $r) {
            if ($r['end_date'] < $today) {
                continue;
            }

            $week = $r['monday'].$r['tuesday'].$r['wednesday'].$r['thursday'].$r['friday'];
            $mask = (str_contains($week, '1') ? 1 : 0) | ($r['saturday'] === '1' ? 2 : 0) | ($r['sunday'] === '1' ? 4 : 0);

            if ($mask) {
                $live[$r['service_id']] = $mask;
            }
        }

        foreach ($csv->rows('calendar_dates.txt', ['service_id', 'date', 'exception_type']) as $r) {
            $day = DateTimeImmutable::createFromFormat('Ymd', $r['date']);

            if ($r['exception_type'] === '1' && $r['date'] >= $today && $day) {
                $n = (int) $day->format('N');
                $live[$r['service_id']] = ($live[$r['service_id']] ?? 0) | ($n === 7 ? 4 : ($n === 6 ? 2 : 1));
            }
        }

        return $live;
    }

    private function routes(Csv $csv): array
    {
        $agencies = [];

        foreach ($csv->rows('agency.txt', ['agency_id', 'agency_name']) as $r) {
            $agencies[$r['agency_id']] = $r['agency_name'];
        }

        $routes = [];

        foreach ($csv->rows('routes.txt', ['route_id', 'agency_id', 'route_short_name', 'route_long_name', 'route_type']) as $r) {
            if ($r['route_type'] === self::BUS) {
                $routes[$r['route_id']] = [
                    $r['route_short_name'] ?: $r['route_id'],
                    $r['route_long_name'] ?: $r['route_short_name'],
                    $agencies[$r['agency_id']] ?? $r['agency_id'],
                ];
            }
        }

        return $routes;
    }

    private function best(Csv $csv, array $routes, array $live): array
    {
        $count = [];

        foreach ($this->trips($csv, $routes, $live) as [$key, $shape]) {
            $count[$key][$shape] = ($count[$key][$shape] ?? 0) + 1;
        }

        return array_map(function (array $c) {
            arsort($c);

            return (string) array_key_first($c);
        }, $count);
    }

    private function reps(Csv $csv, array $routes, array $live, array $best): array
    {
        $reps = [];

        foreach ($this->trips($csv, $routes, $live) as [$key, $shape, $trip]) {
            if (! isset($reps[$key]) && $best[$key] === $shape) {
                $reps[$key] = $trip;
            }
        }

        return $reps;
    }

    private function trips(Csv $csv, array $routes, array $live): Generator
    {
        foreach ($csv->rows('trips.txt', ['route_id', 'service_id', 'trip_id', 'direction_id', 'shape_id']) as $r) {
            if (! isset($routes[$r['route_id']], $live[$r['service_id']])) {
                continue;
            }

            foreach (self::BITS as $b) {
                if ($live[$r['service_id']] & $b) {
                    yield [$r['route_id']."\0".(int) $r['direction_id']."\0".$b, $r['shape_id'], $r['trip_id']];
                }
            }
        }
    }

    private function seqs(Csv $csv, array $reps): array
    {
        $want = array_fill_keys(array_map('strval', $reps), true);
        $seqs = [];

        foreach ($csv->rows('stop_times.txt', ['trip_id', 'stop_id', 'stop_sequence']) as $r) {
            if (isset($want[$r['trip_id']])) {
                $seqs[$r['trip_id']][(int) $r['stop_sequence']] = $r['stop_id'];
            }
        }

        foreach ($seqs as &$s) {
            ksort($s);
            $s = array_values($s);
        }

        return $seqs;
    }

    private function links(array $seqs, array $reps): array
    {
        $masks = [];

        foreach ($reps as $key => $trip) {
            [$line, $dir, $bit] = explode("\0", $key);

            foreach ($seqs[$trip] ?? [] as $i => $stop) {
                $k = $line."\0".$dir."\0".$stop."\0".$i;
                $masks[$k] = ($masks[$k] ?? 0) | (int) $bit;
            }
        }

        $links = [];

        foreach ($masks as $k => $mask) {
            [$line, $dir, $stop, $i] = explode("\0", $k);
            $links[] = new Link($line, $stop, (int) $dir, (int) $i, Mask::days($mask));
        }

        return $links;
    }

    private function lines(array $routes, array $links): array
    {
        $masks = [];

        foreach ($links as $l) {
            $masks[$l->line] = ($masks[$l->line] ?? 0) | Mask::of($l->days);
        }

        $lines = [];

        foreach ($routes as $id => [$code, $name, $agency]) {
            if (isset($masks[$id])) {
                $lines[] = new Line((string) $id, $code, $name, $agency, Mask::days($masks[$id]));
            }
        }

        return $lines;
    }

    private function stops(Csv $csv, array $links): array
    {
        $used = [];

        foreach ($links as $l) {
            $used[$l->stop] = true;
        }

        $stops = [];

        foreach ($csv->rows('stops.txt', ['stop_id', 'stop_name', 'stop_lat', 'stop_lon']) as $r) {
            if (isset($used[$r['stop_id']])) {
                $stops[] = new Stop(
                    $r['stop_id'],
                    $this->name($r['stop_name'], $r['stop_id']),
                    new Point((float) $r['stop_lat'], (float) $r['stop_lon']),
                );
            }
        }

        return $stops;
    }

    private function name(string $name, string $id): string
    {
        $clean = trim((string) preg_replace('/\s*-\s*'.preg_quote($id, '/').'$/', '', trim($name)));

        return $clean === $id ? '' : $clean;
    }

    private function paths(Csv $csv, array $best): array
    {
        $shapes = [];

        foreach ($best as $key => $shape) {
            if ($shape === '') {
                continue;
            }

            [$line, $dir, $bit] = explode("\0", $key);
            $shapes[$shape] = [$line, (int) $dir, ($shapes[$shape][2] ?? 0) | (int) $bit];
        }

        $pts = [];

        foreach ($csv->rows('shapes.txt', ['shape_id', 'shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence']) as $r) {
            if (isset($shapes[$r['shape_id']])) {
                $pts[$r['shape_id']][(int) $r['shape_pt_sequence']] = [(float) $r['shape_pt_lat'], (float) $r['shape_pt_lon']];
            }
        }

        $paths = [];

        foreach ($pts as $id => $seq) {
            ksort($seq);
            [$line, $dir, $mask] = $shapes[$id];
            $paths[] = new Path($line, $dir, Mask::days($mask), array_map(fn (array $p) => new Point($p[0], $p[1]), array_values($seq)));
        }

        return $paths;
    }
}
