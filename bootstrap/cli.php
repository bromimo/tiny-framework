<?php

use App\Facades\Env;
use App\Facades\Cache;
use App\Facades\Config;

require_once __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/..');
require_once __DIR__ . '/../app/Helpers/helpers.php';
Cache::init();
Config::load(__DIR__ . '/../config');

// Инициализация сервисов для CLI (необходимо для queue-команд
// и любых CLI-команд, использующих Container, EventDispatcher или Queue)
$container = new \App\Core\Container();
\App\Facades\App::setInstance($container);

$dispatcher = new \App\Core\EventDispatcher();
\App\Facades\Event::setInstance($dispatcher);
\App\Providers\EventServiceProvider::register($dispatcher);

$queueManager = new \App\Queue\QueueManager(config('queue.default', 'sync'));
\App\Facades\Queue::setInstance($queueManager);
