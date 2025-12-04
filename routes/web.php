<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/status');
});

Route::get('/status', function () {
    return response()->json([
        'status' => 'available',
        'message' => 'Server Available',
    ]);
});
