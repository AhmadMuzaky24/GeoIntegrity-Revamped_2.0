<?php

use App\Http\Controllers\BlockchainController;
use App\Http\Controllers\GeoLocationController;
use App\Models\User;
use App\Support\Actor;
use Illuminate\Support\Facades\Route;

// ponytail: GET switch instead of a login form — demo attribution only.
Route::get('/actor/{user}', function (User $user) {
    session([Actor::SESSION_KEY => $user->id]);

    return back();
})->name('actor.switch');

Route::get('/', [GeoLocationController::class, 'index'])
    ->name('locations.index');

Route::post('/locations', [GeoLocationController::class, 'store'])
    ->name('locations.store');

Route::get('/locations/{geoLocation}', [GeoLocationController::class, 'show'])
    ->name('locations.show');

Route::get('/locations/{geoLocation}/edit', [GeoLocationController::class, 'edit'])
    ->name('locations.edit');

Route::put('/locations/{geoLocation}', [GeoLocationController::class, 'update'])
    ->name('locations.update');

Route::delete('/locations/{geoLocation}', [GeoLocationController::class, 'destroy'])
    ->name('locations.destroy');

Route::get('/chain', [BlockchainController::class, 'index'])
    ->name('chain.index');

Route::get('/chain/verify', [BlockchainController::class, 'verify'])
    ->name('chain.verify');

Route::get('/chain/proof/{geoLocation}', [BlockchainController::class, 'proof'])
    ->name('chain.proof');
