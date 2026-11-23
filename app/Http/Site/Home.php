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
            'fresh' => $when ? Carbon::instance($when)->locale('pt_BR')->translatedFormat('j \\d\\e F') : null,
        ]);
    }
}
