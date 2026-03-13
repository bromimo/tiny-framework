<?php

namespace Tests\Unit\Http\Responses;

use App\Http\Responses\ApiResponse;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_ok_wraps_data_with_200(): void
    {
        $response = ApiResponse::ok(['id' => 1]);
        $this->assertSame(200, $response->getStatus());
        $this->assertSame(['data' => ['id' => 1]], json_decode($response->getBody(), true));
    }

    public function test_created_wraps_data_with_201(): void
    {
        $response = ApiResponse::created(['id' => 1]);
        $this->assertSame(201, $response->getStatus());
        $this->assertSame(['data' => ['id' => 1]], json_decode($response->getBody(), true));
    }

    public function test_error_wraps_message_with_given_status(): void
    {
        $response = ApiResponse::error('Something went wrong', 400);
        $this->assertSame(400, $response->getStatus());
        $this->assertSame(['error' => 'Something went wrong'], json_decode($response->getBody(), true));
    }

    public function test_not_found_returns_404(): void
    {
        $response = ApiResponse::notFound('User not found');
        $this->assertSame(404, $response->getStatus());
    }

    public function test_unauthorized_returns_401(): void
    {
        $response = ApiResponse::unauthorized();
        $this->assertSame(401, $response->getStatus());
    }
}
