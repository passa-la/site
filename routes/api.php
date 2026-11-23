<?php

use App\Http\Api\Lines;
use App\Http\Api\Places;
use App\Http\Api\Walks;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('places', Places::class);
    Route::get('lines', [Lines::class, 'index']);
    Route::get('lines/{id}', [Lines::class, 'show']);
    Route::get('walk', Walks::class);
});
