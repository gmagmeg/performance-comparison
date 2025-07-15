<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReadWeighController;
use App\Http\Controllers\Api\PostWeightController;

Route::get('read-weight', [ReadWeighController::class, 'index']);
Route::post('post-weight', [PostWeightController::class, 'store']);
Route::get('post-weight/{id}', [PostWeightController::class, 'show']);
