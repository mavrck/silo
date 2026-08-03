<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\TagController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->get('/ping', function (Request $request) {
    return response()->json([
        'user' => $request->user()->email,
    ]);
});

Route::middleware(['auth:sanctum', 'jsonapi'])->name('api.')->group(function () {
    Route::get('/user', fn (Request $request) => UserResource::make($request->user()))->name('user');

    Route::apiResource('feeds', FeedController::class)->only(['index', 'show']);
    Route::apiResource('entries', EntryController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('tags', TagController::class)->only(['index', 'show']);
});
