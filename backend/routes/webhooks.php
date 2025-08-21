<?php

use App\Http\Controllers\Webhooks;
use Illuminate\Support\Facades\Route;

Route::post('ses/webhook', [Webhooks\SESWebhookController::class, 'handle'])->name('ses.webhook');

Route::post('/d-id/webhook', [Webhooks\DiDWebhookController::class, 'handle'])->name('d-id.webhook');
Route::post('/heygen/webhook', [Webhooks\HeygenWebhookController::class, 'handle'])->name('heygen.webhook');
Route::post('/tiktok/webhook', [Webhooks\TikTokWebhookController::class, 'handle'])->middleware('verify.tiktok');

Route::prefix('/fastvideo/webhook')->group(function () {
    Route::post('/avatar', [Webhooks\FastVideoWebhookController::class, 'handle'])->name('fastvideo.webhook:avatar');
    Route::post('/clone', [Webhooks\FastVideoCloneWebhookController::class, 'handle'])->name('fastvideo.webhook:clone');
});

Route::post('/remotion/webhook', [Webhooks\RemotionWebhookController::class, 'handle'])->name('remotion.webhook');
Route::post('/creatomate/webhook', [Webhooks\CreatomateWebhookController::class, 'handle'])->name('creatomate.webhook');

Route::post('/minimax/webhook', [Webhooks\MinimaxWebhookController::class, 'handle'])->name('minimax.webhook');
Route::post('/replicate/webhook', [Webhooks\ReplicateWebhookController::class, 'handle'])->name('replicate.webhook');
Route::post('/google-play/webhook', [Webhooks\GooglePlayRtdnController::class, 'handle'])->name('google-play.webhook');
Route::post('/jvzoo/webhook', [Webhooks\JVZooWebhookController::class, 'handle'])->name('jvzoo.webhook');
Route::post('/character-consistency/webhook', [Webhooks\CharacterConsistencyWebhookController::class, 'handle']);

Route::post('/custom-character/webhook/{type}', [Webhooks\CustomCharacterWebhookController::class, 'handle'])
    ->where('type', 'poses|final')
    ->name('custom-character.webhook');
