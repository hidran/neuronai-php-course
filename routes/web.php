<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/conversations');
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])
        ->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');
    Route::controller(ConversationController::class)
        ->group(function () {
            Route::get('/conversations', 'index')
                ->name('conversations.index');
            Route::post('/conversations', 'store')
                ->name('conversations.store');
            Route::get('/conversations/{conversation}', 'show')
                ->name('conversations.show');
        });
});
