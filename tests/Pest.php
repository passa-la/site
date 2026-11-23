<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function gtfs(): string
{
    $file = sys_get_temp_dir().'/passa-gtfs-'.getmypid().'.zip';
    $zip = new ZipArchive;
    $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach (glob(__DIR__.'/Fixtures/gtfs/*.txt') as $f) {
        $zip->addFile($f, basename($f));
    }

    $zip->close();

    return $file;
}
