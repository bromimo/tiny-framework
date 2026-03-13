<?php

namespace Tests\Support;

use App\Facades\DB;
use App\Facades\Env;
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
    }

    protected function tearDown(): void
    {
        DB::query('ROLLBACK');
        parent::tearDown();
    }
}
