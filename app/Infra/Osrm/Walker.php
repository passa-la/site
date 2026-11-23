<?php

namespace App\Infra\Osrm;

use App\Domain\Geo\Point;
use App\Domain\Geo\Walk;
use App\Domain\Geo\Walker as Port;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class Walker implements Port
{
    private const int TTL = 86400;

    public function __construct(
        private Factory $http,
        private Repository $cache,
        private string $url,
    ) {}

    public function walk(Point $from, Point $to): Walk
    {
        $pair = fn (Point $p) => round($p->lon, 5).','.round($p->lat, 5);
        $path = $pair($from).';'.$pair($to);

        $r = $this->cache->remember('osrm:'.md5($path), self::TTL, fn () => $this->fetch($path));

        return new Walk(
            array_map(fn (array $c) => new Point($c[1], $c[0]), $r['coords']),
            $r['meters'],
            $r['seconds'],
        );
    }

    private function fetch(string $path): array
    {
        $res = $this->http
            ->withUserAgent('passa-la/1.0 (+https://github.com/passa-la/site)')
            ->timeout(8)
            ->get($this->url.'/route/v1/foot/'.$path, ['overview' => 'full', 'geometries' => 'geojson'])
            ->throw();

        $route = $res->json('routes.0');

        if ($res->json('code') !== 'Ok' || ! is_array($route)) {
            throw new RuntimeException('No walking route');
        }

        return [
            'coords' => $route['geometry']['coordinates'],
            'meters' => (float) $route['distance'],
            'seconds' => (float) $route['duration'],
        ];
    }
}
