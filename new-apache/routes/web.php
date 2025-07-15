<?php

use App\Http\Controllers\Api\ReadWeighController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/add', function () {
    return view('welcome');
});

Route::get('read-weight', [ReadWeighController::class, 'index']);
Route::get('test-post-weight', function () {
    return view('test-post-weight');
});