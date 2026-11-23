<?php

use App\Cases\Sync;
use App\Domain\Transit\Change;
use App\Domain\Transit\Store;
use App\Infra\Gtfs\Reader;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $feed = (new Reader)->read(gtfs(), '"t1"', '20261123');
    app(Store::class)->save($feed, Change::of($feed->version, [], $feed->net->codes()));
});

it('lists lines within one kilometer, nearest first', function () {
    $this->getJson('/api/v1/lines?lat=-8.0&lon=-34.85')
        ->assertOk()
        ->assertJsonPath('meta.radius', 1000)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.line.code', '100')
        ->assertJsonPath('data.0.stop.id', 'S1')
        ->assertJsonPath('data.0.stop.name', null)
        ->assertJsonPath('data.0.meters', 11)
        ->assertJsonPath('data.1.line.code', '200')
        ->assertJsonPath('data.1.stop.name', 'Ti Teste');
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

it('shows a line with its full path and where to catch it', function () {
    $this->getJson('/api/v1/lines/100?lat=-8.0&lon=-34.85')
        ->assertOk()
        ->assertJsonPath('data.line.name', 'Centro / Praia')
        ->assertJsonCount(2, 'data.paths')
        ->assertJsonPath('data.paths.0.geometry.type', 'LineString')
        ->assertJsonPath('data.paths.0.geometry.coordinates.0', [-34.85, -8.0001])
        ->assertJsonCount(2, 'data.stops')
        ->assertJsonPath('data.near.stop.id', 'S1');
});

it('shows a line without a point', function () {
    $this->getJson('/api/v1/lines/200')
        ->assertOk()
        ->assertJsonPath('data.near', null);
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
