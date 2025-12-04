<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/status');
});

Route::get('/status', function () {
    return response('Server Available', 200)->header('Content-Type', 'text/plain');
});
