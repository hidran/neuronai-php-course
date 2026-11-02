<?php

use App\Http\Controllers\AskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ask', [AskController::class, '__invoke'])->name('ask');
