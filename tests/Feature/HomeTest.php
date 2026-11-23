<?php

use App\Domain\Transit\Change;
use App\Domain\Transit\Store;
use App\Infra\Gtfs\Reader;

it('renders the page with today as the default day', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-29 10:00', new DateTimeZone('America/Recife')));

    $this->get('/')
        ->assertOk()
        ->assertSee('passa lá', false)
        ->assertSee("finder('sunday')", false)
        ->assertDontSee('atualizadas em');
});

it('tells when the lines were last imported', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-23 04:00', new DateTimeZone('America/Recife')));
    $feed = (new Reader)->read(gtfs(), '"t"', '20261123');
    app(Store::class)->save($feed, Change::of($feed->version, [], $feed->net->codes()));

    $this->get('/')->assertSee('atualizadas em 23 de novembro');
});
