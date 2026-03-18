<?php

use TinyRouter\Http\Method;
use TinyRouter\Facade\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('api/v1')->middleware('cors')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('rate_limit:5,60');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    });

    Route::prefix('users')->middleware('auth:api', 'rate_limit:60,60')->group(function () {
        Route::get('', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
        Route::get('{id}', [UserController::class, 'show'])->middleware('can:users.view');
        Route::post('', [UserController::class, 'store'])->middleware('can:users.create');
        Route::match([Method::PUT, Method::PATCH],'{id}', [UserController::class, 'update'])->middleware('can:users.update');
        Route::delete('{id}', [UserController::class, 'destroy'])->middleware('can:users.delete');
    });
});
