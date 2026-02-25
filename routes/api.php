<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthMiddleware;
use TinyRouter\Facade\Route;

Route::group('/api', function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::group('', function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/users',             [UserController::class, 'index']);
        Route::get('/users/{id:\d+}',    [UserController::class, 'show']);
        Route::post('/users',            [UserController::class, 'store']);
        Route::put('/users/{id:\d+}',    [UserController::class, 'update']);
        Route::delete('/users/{id:\d+}', [UserController::class, 'destroy']);

    }, [AuthMiddleware::class]);

});
