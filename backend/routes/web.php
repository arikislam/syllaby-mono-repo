<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacegenRedirectController;

Route::redirect('/', config('app.frontend_url'));

Route::post('/facegen/redirect', FacegenRedirectController::class)->name('facegen.redirect');
