<?php

namespace App\Providers;

use App\Domain\Geo\Box;
use App\Domain\Geo\Geocoder;
use App\Domain\Geo\Point;
use App\Domain\Geo\Region;
use App\Domain\Geo\Walker;
use App\Domain\Transit\Lines;
use App\Domain\Transit\Source;
use App\Domain\Transit\Store;
use App\Infra\Db;
use App\Infra\Gtfs;
use App\Infra\Osrm;
use App\Infra\Photon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Lines::class, Db\Lines::class);
        $this->app->bind(Store::class, Db\Store::class);

        $this->app->bind(Source::class, fn (Application $app) => new Gtfs\Source(
            $app->make(Factory::class),
            new Gtfs\Reader,
            config('transit.feed'),
        ));

        $this->app->bind(Geocoder::class, fn (Application $app) => new Photon\Geocoder(
            $app->make(Factory::class),
            $app->make('cache.store'),
            config('transit.photon'),
        ));

        $this->app->bind(Walker::class, fn (Application $app) => new Osrm\Walker(
            $app->make(Factory::class),
            $app->make('cache.store'),
            config('transit.walker'),
        ));

        $this->app->singleton(Region::class, fn () => new Region(
            Box::of(config('transit.area')),
            new Point(...config('transit.hub')),
            config('transit.state'),
        ));
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));
    }
}
