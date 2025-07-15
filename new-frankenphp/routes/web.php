<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/post-weigh-test', function () {
    return view('post-weigh-test');
});

Route::get('/csv-test', [App\Http\Controllers\CsvTestController::class, 'index']);
Route::get('/csv-test/generate', [App\Http\Controllers\CsvTestController::class, 'generateCsv']);
