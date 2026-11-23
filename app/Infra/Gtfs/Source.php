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
        private string $dir,
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

        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        $file = $this->dir.'/gtfs.zip';
        file_put_contents($file, $res->body());

        return $this->reader->read($file, $new);
    }
}
