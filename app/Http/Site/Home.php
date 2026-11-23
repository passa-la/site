<?php

namespace App\Http\Site;

use App\Domain\Transit\Day;
use App\Domain\Transit\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

final class Home
{
    public function __invoke(Store $store): View
    {
        $when = $store->when();

        return view('pages.home', [
            'today' => Day::of(now())->value,
            'build' => is_file($m = public_path('build/manifest.json')) ? substr((string) md5_file($m), 0, 10) : 'dev',
            'fresh' => $when ? Carbon::instance($when)->locale('pt_BR')->translatedFormat('j \\d\\e F') : null,
        ]);
    }
}
