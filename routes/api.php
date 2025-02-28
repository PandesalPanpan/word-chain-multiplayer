<?php

use App\Http\Controllers\GameRoomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/game-rooms', [GameRoomController::class, 'getActiveRooms'])->name('api.game-rooms');
