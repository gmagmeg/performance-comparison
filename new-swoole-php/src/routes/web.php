<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/posts', [PostController::class, 'index'])->name('posts.index');

Route::get('/', function () {
    return view('welcome');
});

// テスト用のルート