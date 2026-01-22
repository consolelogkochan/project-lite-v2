<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExternalApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ▼▼▼ ★追加: 外部連携用API ▼▼▼
Route::get('/external/boards/{boardId}/summary', [ExternalApiController::class, 'getBoardSummary']);