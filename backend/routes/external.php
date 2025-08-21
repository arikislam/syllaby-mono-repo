<?php

use App\Http\Controllers\External;

Route::prefix('external')->group(function () {
    Route::get('/ideas', [External\IdeaController::class, 'index']);
    Route::post('/ideas/discover', [External\IdeaDiscoverController::class, 'store']);
});
