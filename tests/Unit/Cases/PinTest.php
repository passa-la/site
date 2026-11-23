<?php

use App\Cases\Pin;
use App\Domain\Geo\Geocoder;
use App\Domain\Geo\Place;
use App\Domain\Geo\Point;
use App\Domain\Geo\Region;

function geocoder(?Place $found, bool $fail = false): Geocoder
{
    return new class($found, $fail) implements Geocoder
    {
        public function __construct(private ?Place $found, private bool $fail) {}

        public function find(string $q, Region $r): array
        {
            return [];
        }

        public function at(Point $p): ?Place
        {
            if ($this->fail) {
                throw new RuntimeException('down');
            }

            return $this->found;
        }
    };
}

it('names the tapped point by its address', function () {
    $p = new Point(-7.744, -34.825);
    $place = (new Pin(geocoder(new Place('Rua Argentina', 'Quatro Cantos', new Point(-7.7441, -34.8251), 'Pernambuco'))))($p);

    expect($place->name)->toBe('Rua Argentina')
        ->and($place->label)->toBe('Quatro Cantos')
        ->and($place->point)->toBe($p);
});

it('keeps a plain name when the address is unknown or the service fails', function (bool $fail) {
    $place = (new Pin(geocoder(null, $fail)))(new Point(-7.744, -34.825));

    expect($place->name)->toBe(Pin::NAME)
        ->and($place->label)->toBe('');
})->with(['unknown' => false, 'failing' => true]);
