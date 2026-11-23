<?php

namespace App\Infra\Gtfs;

use Generator;
use ZipArchive;

final readonly class Csv
{
    public function __construct(
        private ZipArchive $zip,
    ) {}

    public function rows(string $name, array $cols): Generator
    {
        $h = $this->zip->getStream($name);

        if (! $h) {
            return;
        }

        $head = fgetcsv($h, escape: '') ?: [];
        $head = array_map(fn (?string $c) => trim(ltrim((string) $c, "\xEF\xBB\xBF")), $head);
        $at = [];

        foreach ($cols as $c) {
            $i = array_search($c, $head, true);
            $at[$c] = $i === false ? null : $i;
        }

        while (($r = fgetcsv($h, escape: '')) !== false) {
            if ($r === [null]) {
                continue;
            }

            $out = [];

            foreach ($at as $c => $i) {
                $out[$c] = $i === null ? '' : trim($r[$i] ?? '');
            }

            yield $out;
        }

        fclose($h);
    }
}
