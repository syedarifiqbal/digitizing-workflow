<?php

use App\Http\Controllers\Api\IntakeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'throttle:api'])->group(function () {
    Route::middleware('api.key')->group(function () {
        Route::post('/v1/intake', IntakeController::class)->name('api.intake');
    });
});
