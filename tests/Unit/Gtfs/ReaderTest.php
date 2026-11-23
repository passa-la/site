<?php

use App\Domain\Transit\Day;
use App\Domain\Transit\Line;
use App\Domain\Transit\Link;
use App\Domain\Transit\Path;
use App\Domain\Transit\Run;
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

it('keeps every trip with its times, grouped by stop pattern', function () {
    $stops = [];

    foreach ($this->feed->net->patterns as $p) {
        $stops[$p->id] = $p->line.':'.$p->dir.':'.implode('>', $p->stops);
    }

    $runs = array_map(
        fn (Run $r) => $stops[$r->pattern].'@'.implode(',', $r->times).':'.implode('+', array_map(fn (Day $d) => $d->value, $r->days)),
        $this->feed->net->runs,
    );
    sort($runs);

    expect(count($stops))->toBe(5)
        ->and($runs)->toBe([
            '100:0:S1>S2>S3@32400,33000,33600:sunday',
            '100:0:S1>S3@18000,18600:weekday',
            '100:0:S1>S3@21600,22200:weekday',
            '100:0:S1>S4@25200,25800:weekday',
            '100:1:S3>S1@21600,22200:weekday',
            '100:1:S3>S1@36000,36600:sunday',
            '100:1:S3>S1@88200,88800:weekday',
            '200:0:S2@25200:weekday',
        ]);
});

it('refuses stop times that split a trip', function () {
    $file = sys_get_temp_dir().'/passa-split-'.getmypid().'.zip';
    $zip = new ZipArchive;
    $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach (glob(__DIR__.'/../../Fixtures/gtfs/*.txt') as $f) {
        $zip->addFile($f, basename($f));
    }

    $zip->addFromString('stop_times.txt', "trip_id,arrival_time,departure_time,stop_id,stop_sequence\nT1,05:00:00,05:00:00,S1,1\nT7,06:00:00,06:00:00,S1,1\nT1,05:10:00,05:10:00,S3,2\n");
    $zip->close();

    expect(fn () => (new Reader)->read($file, null, '20261123'))->toThrow(RuntimeException::class);
});
