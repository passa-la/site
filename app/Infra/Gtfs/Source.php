<?php

namespace App\Infra\Gtfs;

use App\Domain\Transit\Feed;
use App\Domain\Transit\Source as Port;
use Illuminate\Http\Client\Factory;

final readonly class Source implements Port
{
    public function __construct(
        private Factory $http,
        private Reader $reader,
        private string $url,
    ) {}

    public function pull(?string $tag): ?Feed
    {
        $res = $this->http
            ->withUserAgent('passa-la/1.0 (+https://github.com/passa-la/site)')
            ->withHeaders(array_filter(['If-None-Match' => $tag]))
            ->timeout(300)
            ->get($this->url);

        if ($res->status() === 304) {
            return null;
        }

        $res->throw();
        $new = $res->header('ETag') ?: null;

        if ($tag !== null && $new === $tag) {
            return null;
        }

        $file = (string) tempnam(sys_get_temp_dir(), 'gtfs');

        try {
            file_put_contents($file, $res->body());

            return $this->reader->read($file, $new);
        } finally {
            @unlink($file);
        }
    }
}
