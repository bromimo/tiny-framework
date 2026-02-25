<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;

class UuidTest extends TestCase
{
    public function test_generates_valid_uuid_v4(): void
    {
        $uuid = generateUuid();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function test_generates_unique_uuids(): void
    {
        $uuids = array_map(fn() => generateUuid(), range(1, 100));
        $this->assertCount(100, array_unique($uuids));
    }
}
