<?php

use TinyRouter\Facade\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('api/v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('rate_limit:5,60');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    });

    Route::prefix('users')->middleware('auth:api')->group(function () {
        Route::get('', [UserController::class, 'index'])->name('users.index');
        Route::get('{id}', [UserController::class, 'show']);
        Route::post('', [UserController::class, 'store']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
    });
});
