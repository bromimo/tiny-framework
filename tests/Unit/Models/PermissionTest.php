<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use Tests\Support\FeatureTestCase;

class PermissionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        qi("INSERT IGNORE INTO permissions (id, name, description) VALUES (1, 'users.view', 'Просмотр пользователей')");
    }

    public function test_find_by_name_returns_permission(): void
    {
        $perm = Permission::findByName('users.view');

        $this->assertInstanceOf(Permission::class, $perm);
        $this->assertSame('users.view', $perm->name);
    }

    public function test_find_by_name_returns_null_for_unknown(): void
    {
        $this->assertNull(Permission::findByName('nonexistent'));
    }
}
