<?php

namespace App\Http\Site;

use App\Domain\Transit\Day;
use Illuminate\Contracts\View\View;

final class Home
{
    public function __invoke(): View
    {
        return view('pages.home', ['today' => Day::of(now())->value]);
    }
}
