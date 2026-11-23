<?php

use App\Cases\Sync;
use App\Domain\Transit\Change;
use App\Domain\Transit\Store;
use App\Infra\Gtfs\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $feed = (new Reader)->read(gtfs(), '"t1"', '20261123');
    app(Store::class)->save($feed, Change::of($feed->version, [], $feed->net->codes()));
});

it('lists lines within one kilometer, nearest first', function () {
    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85&day=weekday')
        ->assertOk()
        ->assertJsonPath('meta.radius', 1000)
        ->assertJsonPath('meta.day', 'weekday')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.line.code', '100')
        ->assertJsonPath('data.0.stop.id', 'S1')
        ->assertJsonPath('data.0.stop.name', null)
        ->assertJsonPath('data.0.meters', 11)
        ->assertJsonPath('data.1.line.code', '200')
        ->assertJsonPath('data.1.stop.name', 'Ti Teste');
});

it('lists only lines that run on the chosen day', function () {
    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85&day=sunday')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.line.code', '100')
        ->assertJsonPath('data.0.line.days', ['weekday', 'sunday']);
});

it('defaults to the current service day', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-29 10:00', new DateTimeZone('America/Recife')));

    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85')->assertJsonPath('meta.day', 'sunday');
});

it('rejects unknown days', function () {
    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85&day=holiday')->assertUnprocessable();
});

it('shrinks the radius on request', function () {
    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85&day=weekday&radius=300')
        ->assertOk()
        ->assertJsonPath('meta.radius', 300)
        ->assertJsonPath('meta.wide', false)
        ->assertJsonPath('data.*.line.code', ['100']);
});

it('never searches wider than one kilometer on request', function () {
    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85&radius=5000')->assertUnprocessable();
});

it('shows the nearest lines when none passes within the radius', function () {
    $this->getJson('/api/v1/lines?lat=-8.04&lon=-34.85&day=weekday')
        ->assertOk()
        ->assertJsonPath('meta.wide', true)
        ->assertJsonPath('data.*.line.code', ['100', '200'])
        ->assertJsonPath('data.0.stop.id', 'S3');
});

it('returns nothing far from every stop', function () {
    $this->getJson('/api/v1/lines?lat=-8.3&lon=-35.0')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('validates coordinates', function () {
    $this->getJson('/api/v1/lines?lat=abc')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lat', 'lon']);
});

it('shows both directions of the day with where to get off', function () {
    $this->getJson('/api/v1/lines/100?day=weekday&lat=-8.02&lon=-34.85')
        ->assertOk()
        ->assertJsonPath('data.line.name', 'Centro / Praia')
        ->assertJsonPath('data.day', 'weekday')
        ->assertJsonPath('data.runs', true)
        ->assertJsonPath('data.best', null)
        ->assertJsonCount(2, 'data.ways')
        ->assertJsonPath('data.ways.0.geometry.type', 'LineString')
        ->assertJsonPath('data.ways.0.geometry.coordinates.0', [-34.85, -8.0001])
        ->assertJsonPath('data.ways.0.stops.*.id', ['S1', 'S3'])
        ->assertJsonPath('data.ways.0.drop.stop.id', 'S3')
        ->assertJsonPath('data.ways.0.board', null)
        ->assertJsonPath('data.ways.1.stops.*.id', ['S3', 'S1']);
});

it('follows the sunday trip on sundays', function () {
    $this->getJson('/api/v1/lines/100?day=sunday')
        ->assertOk()
        ->assertJsonPath('data.ways.0.stops.*.id', ['S1', 'S2', 'S3'])
        ->assertJsonCount(3, 'data.ways.0.geometry.coordinates');
});

it('recommends the direction that goes from the rider to the destination', function () {
    $this->getJson('/api/v1/lines/100?day=weekday&lat=-8.02&lon=-34.85&from_lat=-8.0&from_lon=-34.85')
        ->assertJsonPath('data.best', 0)
        ->assertJsonPath('data.ways.0.board.stop.id', 'S1')
        ->assertJsonPath('data.ways.0.fits', true)
        ->assertJsonPath('data.ways.1.fits', false);

    $this->getJson('/api/v1/lines/100?day=weekday&lat=-8.0&lon=-34.85&from_lat=-8.02&from_lon=-34.85')
        ->assertJsonPath('data.best', 1);
});

it('boards at the stop the rider tapped', function () {
    $this->getJson('/api/v1/lines/100?day=weekday&lat=-8.0&lon=-34.85&from_lat=-8.0&from_lon=-34.85&stop=S3')
        ->assertJsonPath('data.best', 1)
        ->assertJsonPath('data.ways.1.board.stop.id', 'S3');
});

it('says when a line does not run that day', function () {
    $this->getJson('/api/v1/lines/200?day=sunday')
        ->assertOk()
        ->assertJsonPath('data.runs', false)
        ->assertJsonCount(0, 'data.ways');
});

it('finds direct lines between two places', function () {
    $this->getJson('/api/v1/rides?day=weekday&from_lat=-8.0&from_lon=-34.85&lat=-8.02&lon=-34.85')
        ->assertOk()
        ->assertJsonPath('meta.day', 'weekday')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.line.code', '100')
        ->assertJsonPath('data.0.dir', 0)
        ->assertJsonPath('data.0.board.stop.id', 'S1')
        ->assertJsonPath('data.0.drop.stop.id', 'S3')
        ->assertJsonPath('data.0.hops', 1)
        ->assertJsonPath('data.0.walk', 11);

    $this->getJson('/api/v1/rides?day=weekday&from_lat=-8.02&from_lon=-34.85&lat=-8.0&lon=-34.85')
        ->assertJsonPath('data.0.dir', 1);
});

it('follows the day when looking for direct lines', function () {
    $this->getJson('/api/v1/rides?day=weekday&from_lat=-8.0&from_lon=-34.85&lat=-8.005&lon=-34.85')
        ->assertJsonCount(0, 'data');

    $this->getJson('/api/v1/rides?day=sunday&from_lat=-8.0&from_lon=-34.85&lat=-8.005&lon=-34.85&radius=300')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.drop.stop.id', 'S2');
});

it('needs both ends of the ride', function () {
    $this->getJson('/api/v1/rides?lat=-8.0&lon=-34.85')->assertUnprocessable();
});

it('routes the walk to the stop', function () {
    Http::fake(['routing.openstreetmap.de/*' => Http::response([
        'code' => 'Ok',
        'routes' => [['distance' => 507.3, 'duration' => 410, 'geometry' => ['type' => 'LineString', 'coordinates' => [[-34.8394, -7.9947], [-34.841, -7.994], [-34.843, -7.9939]]]]],
    ])]);

    $this->getJson('/api/v1/walk?from_lat=-7.9947&from_lon=-34.8394&lat=-7.9939&lon=-34.843')
        ->assertOk()
        ->assertJsonPath('data.meters', 507)
        ->assertJsonPath('data.minutes', 7)
        ->assertJsonPath('data.exact', true)
        ->assertJsonCount(3, 'data.geometry.coordinates');
});

it('walks in a straight line when routing is down', function () {
    Http::fake(['routing.openstreetmap.de/*' => Http::response('', 503)]);

    $this->getJson('/api/v1/walk?from_lat=-7.9947&from_lon=-34.8394&lat=-7.9939&lon=-34.843')
        ->assertOk()
        ->assertJsonPath('data.exact', false)
        ->assertJsonCount(2, 'data.geometry.coordinates');
});

it('needs both ends of the walk', function () {
    $this->getJson('/api/v1/walk?lat=-7.9939&lon=-34.843')->assertUnprocessable();
});

it('answers 404 for unknown lines', function () {
    $this->getJson('/api/v1/lines/999')->assertNotFound();
});

it('suggests places in the state only, skipping bus stops', function () {
    Http::fake(['photon.komoot.io/*' => Http::response(['features' => [
        ['geometry' => ['coordinates' => [-34.8407, -7.9939]], 'properties' => ['name' => 'Patteo Olinda Shopping', 'street' => 'Rua Doutor Eduardo de Morais', 'district' => 'Casa Caiada', 'city' => 'Olinda', 'state' => 'Pernambuco', 'osm_key' => 'shop', 'osm_value' => 'mall']],
        ['geometry' => ['coordinates' => [-34.8394, -7.9947]], 'properties' => ['name' => 'Shopping Patteo Olinda', 'city' => 'Olinda', 'state' => 'Pernambuco', 'osm_key' => 'highway', 'osm_value' => 'bus_stop']],
        ['geometry' => ['coordinates' => [-39.31, -7.21]], 'properties' => ['name' => 'Pantec', 'city' => 'Juazeiro do Norte', 'state' => 'Ceará', 'osm_key' => 'shop', 'osm_value' => 'mall']],
    ]])]);

    $this->getJson('/api/v1/places?q=patteo')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Patteo Olinda Shopping')
        ->assertJsonPath('data.0.label', 'Rua Doutor Eduardo de Morais, Casa Caiada, Olinda');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'bbox=') && $r['q'] === 'patteo');
});

it('names a tapped point', function () {
    Http::fake(['photon.komoot.io/reverse*' => Http::response(['features' => [
        ['geometry' => ['coordinates' => [-34.825, -7.744]], 'properties' => ['name' => 'Ponto 1', 'osm_key' => 'highway', 'osm_value' => 'bus_stop', 'state' => 'Pernambuco']],
        ['geometry' => ['coordinates' => [-34.825, -7.744]], 'properties' => ['name' => 'Rua Argentina', 'district' => 'Quatro Cantos', 'city' => 'Ilha de Itamaracá', 'state' => 'Pernambuco', 'osm_key' => 'highway', 'osm_value' => 'residential']],
    ]])]);

    $this->getJson('/api/v1/places/reverse?lat=-7.744&lon=-34.825')
        ->assertOk()
        ->assertJsonPath('data.name', 'Rua Argentina')
        ->assertJsonPath('data.label', 'Quatro Cantos, Ilha de Itamaracá')
        ->assertJsonPath('data.lat', -7.744);
});

it('finds lines by code or name, ignoring accents and leading zeros', function (string $q, array $codes) {
    $this->getJson('/api/v1/lines/search?q='.urlencode($q))
        ->assertOk()
        ->assertJsonPath('data.*.code', $codes);
})->with([
    'code' => ['200', ['200']],
    'prefix' => ['1', ['100']],
    'name' => ['praia', ['100']],
    'accent' => ['PRACA', ['200']],
    'nothing' => ['zzz', []],
]);

it('requires a query of three letters', function () {
    $this->getJson('/api/v1/places?q=ab')->assertUnprocessable();
});

it('syncs only when the feed tag changes', function () {
    Http::fake(['*' => Http::sequence()
        ->push(file_get_contents(gtfs()), 200, ['ETag' => '"t2"'])
        ->push('', 304)]);

    $first = app(Sync::class)();
    $second = app(Sync::class)();

    expect($first->version)->toBe('v-test')
        ->and($first->empty())->toBeTrue()
        ->and($second)->toBeNull();

    $this->assertDatabaseHas('syncs', ['tag' => '"t2"']);
    Http::assertSent(fn ($r) => $r->header('If-None-Match') === ['"t2"']);
});

it('writes a report of what changed in the feed', function () {
    $file = tempnam(sys_get_temp_dir(), 'report');
    Http::fake(['*' => Http::sequence()
        ->push(file_get_contents(gtfs()), 200, ['ETag' => '"t3"'])
        ->push('', 304)]);
    DB::table('lines')->where('code', '200')->delete();

    $this->artisan('gtfs:sync', ['--report' => $file])->assertSuccessful();

    expect(file_get_contents($file))
        ->toContain('Feed `v-test` · 2 linhas de ônibus')
        ->toContain('**Entraram (1):** 200')
        ->toContain('**Saíram:** nenhuma')
        ->toContain('<!-- changed -->');

    $this->artisan('gtfs:sync', ['--report' => $file])->assertSuccessful();

    expect(file_get_contents($file))
        ->toContain('O feed não mudou')
        ->not->toContain('<!-- changed -->');

    unlink($file);
});

it('shows the next buses at the stop the rider tapped', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-23 05:30', new DateTimeZone('America/Recife')));

    $this->getJson('/api/v1/lines/100?stop=S1')
        ->assertOk()
        ->assertJsonPath('data.best', 0)
        ->assertJsonPath('data.ways.0.times.stop', 'S1')
        ->assertJsonPath('data.ways.0.times.end', null)
        ->assertJsonPath('data.ways.0.times.ride', null)
        ->assertJsonPath('data.ways.0.times.next', [['at' => '06:00', 'in' => 30, 'end' => null], ['at' => '07:00', 'in' => 90, 'end' => null]])
        ->assertJsonPath('data.ways.0.times.all', ['05:00', '06:00', '07:00'])
        ->assertJsonPath('data.ways.1.times', null);
});

it('tells when the bus gets to the stop the rider gets off', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-23 05:30', new DateTimeZone('America/Recife')));

    $this->getJson('/api/v1/lines/100?stop=S1&end=S3')
        ->assertOk()
        ->assertJsonPath('data.ways.0.drop.stop.id', 'S3')
        ->assertJsonPath('data.ways.0.fits', true)
        ->assertJsonPath('data.ways.0.times.end', 'S3')
        ->assertJsonPath('data.ways.0.times.ride', 10)
        ->assertJsonPath('data.ways.0.times.next', [['at' => '06:00', 'in' => 30, 'end' => '06:10']])
        ->assertJsonPath('data.ways.0.times.all', ['05:00', '06:00']);
});

it('keeps the late night buses of the day before', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-24 00:20', new DateTimeZone('America/Recife')));

    $this->getJson('/api/v1/lines/100?stop=S3')
        ->assertJsonPath('data.best', 1)
        ->assertJsonPath('data.ways.1.times.next.*.at', ['00:30', '06:00', '00:30'])
        ->assertJsonPath('data.ways.1.times.next.0.in', 10)
        ->assertJsonPath('data.ways.1.times.all', ['06:00', '00:30']);
});

it('shows only the table on another day', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-29 10:00', new DateTimeZone('America/Recife')));

    $this->getJson('/api/v1/lines/100?day=weekday&stop=S1')
        ->assertJsonPath('data.ways.0.times.next', null)
        ->assertJsonPath('data.ways.0.times.all', ['05:00', '06:00', '07:00']);
});

it('has no times without a boarding stop', function () {
    $this->getJson('/api/v1/lines/100?day=weekday')
        ->assertJsonPath('data.ways.0.times', null);
});
