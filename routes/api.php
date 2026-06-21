<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UndanganController;
use App\Http\Controllers\Api\RsvpController;
use App\Http\Controllers\Api\GuestController;


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::apiResource('undangan', UndanganController::class);

Route::get('/undangan', [UndanganController::class, 'index']);
Route::get('/undangan/{undangan}/rsvps', [RsvpController::class, 'index']);
Route::delete('/undangan/delete/{slug}', [UndanganController::class, 'destroyBySlug']);

Route::post('/rsvps/{slug}', [RsvpController::class, 'storeBySlug']);
Route::get('/rsvps/{slug}', [RsvpController::class, 'showBySlug']);

Route::post('/undangan/{slug}/guests', [GuestController::class, 'store']);
Route::get('/undangan/{slug}/guests', [GuestController::class, 'index']);
Route::put('/undangan/{slug}/guests/{id}', [GuestController::class, 'update']);
Route::delete('/undangan/{slug}/guests/{id}', [GuestController::class, 'destroy']);

Route::get('/invitation/{slug}', [UndanganController::class, 'showBySlug']);

Route::middleware(\App\Http\Middleware\VerifyClientToken::class)->group(function() {
    Route::match(['post', 'put'], '/client/undangan/{undangan}', [UndanganController::class, 'update']);
    Route::post('/client/undangan/{slug}/guests', [GuestController::class, 'store']);
    Route::put('/client/undangan/{slug}/guests/{id}', [GuestController::class, 'update']);
    Route::delete('/client/undangan/{slug}/guests/{id}', [GuestController::class, 'destroy']);
});
