<?php

use App\Domain\Transit\Day;
use App\Domain\Transit\Line;
use App\Domain\Transit\Link;
use App\Domain\Transit\Path;
use App\Infra\Gtfs\Reader;

beforeEach(function () {
    $this->feed = (new Reader)->read(gtfs(), '"tag"', '20261123');
});

it('reads version and tag', function () {
    expect($this->feed->version)->toBe('v-test')
        ->and($this->feed->tag)->toBe('"tag"');
});

it('keeps only live bus lines with the days they run', function () {
    $lines = $this->feed->net->lines;

    expect(array_map(fn (Line $l) => $l->code, $lines))->toBe(['100', '200'])
        ->and($lines[0]->days)->toBe([Day::Weekday, Day::Sunday])
        ->and($lines[1]->days)->toBe([Day::Weekday])
        ->and($lines[0]->agency)->toBe('Empresa Teste');
});

it('cleans stop names and keeps only served stops', function () {
    $names = [];

    foreach ($this->feed->net->stops as $s) {
        $names[$s->id] = $s->name;
    }

    expect($names)->toBe(['S1' => '', 'S2' => 'Ti Teste', 'S3' => '']);
});

it('orders stops per direction and day from the main trip', function () {
    $links = array_map(
        fn (Link $l) => $l->line.':'.$l->dir.':'.$l->seq.':'.$l->stop.':'.implode('+', array_map(fn (Day $d) => $d->value, $l->days)),
        $this->feed->net->links,
    );
    sort($links);

    expect($links)->toBe([
        '100:0:0:S1:weekday+sunday',
        '100:0:1:S2:sunday',
        '100:0:1:S3:weekday',
        '100:0:2:S3:sunday',
        '100:1:0:S3:weekday+sunday',
        '100:1:1:S1:weekday+sunday',
        '200:0:0:S2:weekday',
    ]);
});

it('keeps the most used shape of each direction per day', function () {
    $paths = array_map(
        fn (Path $p) => $p->line.':'.$p->dir.':'.implode('+', array_map(fn (Day $d) => $d->value, $p->days)).':'.count($p->points),
        $this->feed->net->paths,
    );
    sort($paths);

    expect($paths)->toBe([
        '100:0:sunday:3',
        '100:0:weekday:3',
        '100:1:weekday+sunday:2',
        '200:0:weekday:2',
    ]);

    $week = array_values(array_filter($this->feed->net->paths, fn (Path $p) => $p->line === '100' && $p->dir === 0 && $p->days === [Day::Weekday]))[0];

    expect(array_map(fn ($pt) => $pt->lat, $week->points))->toBe([-8.0001, -8.01, -8.02]);
});
