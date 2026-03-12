<?php

use App\Facades\Env;
use App\Facades\Cache;
use App\Facades\Config;

require_once __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/..');
require_once __DIR__ . '/../app/Helpers/helpers.php';
Cache::init();
Config::load(__DIR__ . '/../config');
