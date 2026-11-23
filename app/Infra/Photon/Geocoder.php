<?php

namespace App\Infra\Photon;

use App\Domain\Geo\Geocoder as Port;
use App\Domain\Geo\Place;
use App\Domain\Geo\Point;
use App\Domain\Geo\Region;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;

final readonly class Geocoder implements Port
{
    private const int TTL = 86400;

    private const int LIMIT = 10;

    private const string AGENT = 'passa-la/1.0 (+https://github.com/passa-la/site)';

    public function __construct(
        private Factory $http,
        private Repository $cache,
        private string $url,
    ) {}

    public function find(string $q, Region $r): array
    {
        $rows = $this->cache->remember(
            'photon:v2:'.md5(mb_strtolower($q)),
            self::TTL,
            fn () => $this->fetch($q, $r),
        );

        return array_map(fn (array $p) => new Place($p['name'], $p['label'], new Point($p['lat'], $p['lon']), $p['state']), $rows);
    }

    public function at(Point $p): ?Place
    {
        $key = 'photon:at:'.round($p->lat, 4).','.round($p->lon, 4);
        $row = $this->cache->remember($key, self::TTL, fn () => $this->near($p));

        return $row ? new Place($row['name'], $row['label'], $p, $row['state']) : null;
    }

    private function near(Point $p): ?array
    {
        $res = $this->http
            ->baseUrl($this->url)
            ->withUserAgent(self::AGENT)
            ->timeout(8)
            ->get('/reverse', ['lat' => $p->lat, 'lon' => $p->lon, 'limit' => 3])
            ->throw();

        foreach ($res->json('features') ?? [] as $f) {
            if ($found = $this->place($f)) {
                return $found;
            }
        }

        return null;
    }

    private function fetch(string $q, Region $r): array
    {
        $b = $r->box;

        $res = $this->http
            ->baseUrl($this->url)
            ->withUserAgent(self::AGENT)
            ->timeout(8)
            ->get('/api', [
                'q' => $q,
                'limit' => self::LIMIT,
                'bbox' => implode(',', [$b->min->lon, $b->min->lat, $b->max->lon, $b->max->lat]),
                'lat' => $r->hub->lat,
                'lon' => $r->hub->lon,
            ])
            ->throw();

        $seen = [];
        $out = [];

        foreach ($res->json('features') ?? [] as $f) {
            $p = $this->place($f);

            if ($p && ! isset($seen[$p['name'].$p['label']])) {
                $seen[$p['name'].$p['label']] = true;
                $out[] = $p;
            }
        }

        return $out;
    }

    private function place(array $f): ?array
    {
        $c = $f['geometry']['coordinates'] ?? null;
        $p = $f['properties'] ?? [];

        if (! is_array($c) || count($c) < 2 || $this->stop($p)) {
            return null;
        }

        $street = trim(($p['street'] ?? '').' '.($p['housenumber'] ?? ''));
        $name = $p['name'] ?? ($street ?: ($p['city'] ?? null));

        if (! $name) {
            return null;
        }

        $label = array_unique(array_filter([
            $street === $name ? null : $street,
            $p['district'] ?? $p['locality'] ?? null,
            $p['city'] ?? null,
        ]));

        return [
            'name' => $name,
            'label' => implode(', ', array_diff($label, [$name])),
            'lat' => (float) $c[1],
            'lon' => (float) $c[0],
            'state' => (string) ($p['state'] ?? ''),
        ];
    }

    private function stop(array $p): bool
    {
        $key = $p['osm_key'] ?? '';
        $value = $p['osm_value'] ?? '';

        return $key === 'public_transport' || ($key === 'highway' && $value === 'bus_stop');
    }
}
