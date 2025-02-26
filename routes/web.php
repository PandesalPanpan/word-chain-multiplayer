<?php

use App\Http\Controllers\GameRoomController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Send a test log
Route::post('/log', function () {
    logger('Axios Post worked');
});

Route::middleware('auth')->group(function () {
    Route::get('/game-rooms', [GameRoomController::class, 'index'])->name('game-rooms.index');
    Route::post('/game-rooms', [GameRoomController::class, 'store'])->name('game-rooms.store');
    Route::get('/game-rooms/create', [GameRoomController::class, 'create'])->name('game-rooms.create');
    // Make wild cards routes are the last routes
    Route::get('/game-rooms/{gameRoom}', [GameRoomController::class, 'show'])->name('game-rooms.show');
    Route::post('/game-rooms/{gameRoom}/leave', [GameRoomController::class, 'leave'])->name('game-rooms.leave');

//    Route::post('/game-rooms/{gameRoom}/word-moves', [WordMoveController::class, 'store'])->name('word-moves.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
