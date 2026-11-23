<?php

use App\Domain\Transit\Line;
use App\Domain\Transit\Path;
use App\Domain\Transit\Stop;
use App\Infra\Gtfs\Reader;

beforeEach(function () {
    $this->feed = (new Reader)->read(gtfs(), '"tag"', '20261123');
});

it('reads version and tag', function () {
    expect($this->feed->version)->toBe('v-test')
        ->and($this->feed->tag)->toBe('"tag"');
});

it('keeps only live bus lines', function () {
    expect(array_map(fn (Line $l) => $l->code, $this->feed->net->lines))->toBe(['100', '200'])
        ->and($this->feed->net->lines[0]->agency)->toBe('Empresa Teste');
});

it('links stops to the lines that serve them', function () {
    $stops = [];

    foreach ($this->feed->net->stops as $s) {
        $stops[$s->id] = $s;
    }

    expect(array_keys($stops))->toBe(['S1', 'S2', 'S3'])
        ->and($stops['S1']->lines)->toBe(['100'])
        ->and($stops['S2']->lines)->toBe(['200'])
        ->and($stops['S1']->name)->toBe('')
        ->and($stops['S2']->name)->toBe('Ti Teste')
        ->and($stops['S1'])->toBeInstanceOf(Stop::class);
});

it('keeps the most used shape per direction in sequence order', function () {
    $paths = [];

    foreach ($this->feed->net->paths as $p) {
        $paths[$p->line.':'.$p->dir] = $p;
    }

    ksort($paths);

    expect(array_keys($paths))->toBe(['100:0', '100:1', '200:0'])
        ->and(array_map(fn ($pt) => $pt->lat, $paths['100:0']->points))->toBe([-8.0001, -8.01, -8.02])
        ->and($paths['200:0'])->toBeInstanceOf(Path::class);
});
