<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'INVOXA',
        'status' => 'running',
        'message' => 'Welcome to INVOXA API',
        'health_check' => url('/api/health'),
    ]);
});