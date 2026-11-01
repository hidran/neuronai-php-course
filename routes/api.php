<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\ConversationMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ask', AskController::class)->name('ask');

Route::post(
    '/conversations/{conversation}/messages',
    ConversationMessageController::class,
)->middleware('auth:sanctum')->name('conversations.messages');
