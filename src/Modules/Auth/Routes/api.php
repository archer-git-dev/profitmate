<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('/test', fn() => response()->json(['message' => 'Auth module is loaded!']));
});
