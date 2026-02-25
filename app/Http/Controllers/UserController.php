<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\Logger;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;

class UserController
{
    public function index(Request $req): Response
    {
        $users = array_map(
            fn($user) => User::withoutPassword($user),
            User::findAll()
        );

        return ApiResponse::ok($users);
    }

    public function show(Request $req): Response
    {
        $user = User::findById((int) $req->params['id']);

        if ($user === null) {
            return ApiResponse::notFound('User not found.');
        }

        return ApiResponse::ok(User::withoutPassword($user));
    }

    public function store(Request $req): Response
    {
        $request = new CreateUserRequest();
        $errors  = $request->validate();

        if (!empty($errors)) {
            return ApiResponse::error($errors, 422);
        }

        $dto  = $request->toDto();
        $user = User::create($dto);

        Logger::info("User created: {$user['id']}");

        return ApiResponse::created(User::withoutPassword($user));
    }

    public function update(Request $req): Response
    {
        $user = User::findById((int) $req->params['id']);

        if ($user === null) {
            return ApiResponse::notFound('User not found.');
        }

        $request = new UpdateUserRequest();
        $errors  = $request->validate();

        if (!empty($errors)) {
            return ApiResponse::error($errors, 422);
        }

        $dto     = $request->toDto();
        $updated = User::update($user['id'], $dto);

        Logger::info("User updated: {$user['id']}");

        return ApiResponse::ok(User::withoutPassword($updated));
    }

    public function destroy(Request $req): Response
    {
        $user = User::findById((int) $req->params['id']);

        if ($user === null) {
            return ApiResponse::notFound('User not found.');
        }

        User::deleteById($user['id']);

        Logger::info("User deleted: {$user['id']}");

        return ApiResponse::ok(['message' => 'User deleted successfully.']);
    }
}
