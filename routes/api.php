<?php

use App\Http\Api\Lines;
use App\Http\Api\Places;
use App\Http\Api\Walks;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('places', [Places::class, 'index']);
    Route::get('places/reverse', [Places::class, 'reverse']);
    Route::get('lines', [Lines::class, 'index']);
    Route::get('lines/search', [Lines::class, 'search']);
    Route::get('lines/{id}', [Lines::class, 'show']);
    Route::get('walk', Walks::class);
});
