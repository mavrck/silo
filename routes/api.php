<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->get('/ping', function (Request $request) {
    return response()->json([
        'user' => $request->user()->email,
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'email' => $request->user()->email,
    ]);
});
