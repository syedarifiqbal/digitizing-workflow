<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'throttle:api'])->group(function () {
    //
});

