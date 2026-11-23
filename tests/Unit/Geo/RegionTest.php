<?php

use App\Domain\Geo\Box;
use App\Domain\Geo\Place;
use App\Domain\Geo\Point;
use App\Domain\Geo\Region;

beforeEach(function () {
    $this->region = new Region(Box::of([-9.6, -41.5, -7.1, -34.7]), new Point(-8.06, -34.87), 'Pernambuco');
});

it('keeps places inside the state', function () {
    expect($this->region->has(new Place('Patteo', 'Olinda', new Point(-7.99, -34.84), 'Pernambuco')))->toBeTrue()
        ->and($this->region->has(new Place('Sem estado', '', new Point(-8.0, -34.9))))->toBeTrue();
});

it('drops places from other states inside the box', function () {
    expect($this->region->has(new Place('Pantec', 'Juazeiro do Norte', new Point(-7.21, -39.31), 'Ceará')))->toBeFalse();
});

it('drops places outside the box', function () {
    expect($this->region->has(new Place('Avenida Paulista', 'São Paulo', new Point(-23.56, -46.65), 'Pernambuco')))->toBeFalse();
});
