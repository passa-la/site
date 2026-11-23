<?php

use App\Domain\Transit\Day;

it('maps dates to service days', function (string $date, Day $day) {
    expect(Day::of(new DateTimeImmutable($date)))->toBe($day);
})->with([
    'monday' => ['2026-11-23', Day::Weekday],
    'friday' => ['2026-11-27', Day::Weekday],
    'saturday' => ['2026-11-28', Day::Saturday],
    'sunday' => ['2026-11-29', Day::Sunday],
]);
