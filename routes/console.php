<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('gtfs:sync')->weeklyOn(1, '04:00')->withoutOverlapping();
