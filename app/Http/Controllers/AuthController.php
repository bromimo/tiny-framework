<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Token;
use App\Models\User;
use App\Support\Logger;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;

class AuthController
{
    public function login(Request $req): Response
    {
        $request = new LoginRequest();
        $errors  = $request->validate();

        if (!empty($errors)) {
            return ApiResponse::error($errors, 422);
        }

        $dto  = $request->toDto();
        $user = User::findByEmail($dto->email);

        if ($user === null || !password_verify($dto->password, $user['password'])) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        $token = Token::create($user['id']);

        Logger::info("User {$user['id']} logged in.");

        return ApiResponse::ok([
            'token'      => $token['token'],
            'expires_at' => $token['expires_at'],
        ]);
    }

    public function logout(Request $req): Response
    {
        $token = getBearerToken();

        if ($token === null) {
            return ApiResponse::unauthorized('No token provided.');
        }

        Token::deleteByToken($token);

        return ApiResponse::ok(['message' => 'Logged out successfully.']);
    }
}
