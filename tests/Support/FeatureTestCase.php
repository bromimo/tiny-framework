<?php

namespace Tests\Support;

use App\Facades\DB;
use App\Facades\Env;
use App\Facades\Auth;
use App\Facades\Cache;
use App\Facades\Config;
use PHPUnit\Framework\TestCase;

/** Базовый класс для HTTP-интеграционных тестов.
 * Каждый тест выполняется внутри транзакции, которая откатывается в tearDown.
 * ВАЖНО: не вызывать Database::reset() или DB::reset() в дочерних классах — это разорвёт транзакцию.
 */
abstract class FeatureTestCase extends TestCase
{
    protected TestClient $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Env::safeLoad(base_path(), '.env.testing');
        Cache::init();
        Config::load(base_path('config'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TestClient();
        DB::query('START TRANSACTION');

        // Минимальные данные RBAC для всех feature-тестов
        qi("INSERT IGNORE INTO roles (id, name) VALUES (1, 'user')");
        qi("INSERT IGNORE INTO roles (id, name) VALUES (2, 'admin')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (1, 'users.view')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (2, 'users.create')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (3, 'users.update')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (4, 'users.delete')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (5, '*')");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)");
    }

    protected function tearDown(): void
    {
        DB::query('ROLLBACK');
        Auth::reset();
        parent::tearDown();
    }
}
