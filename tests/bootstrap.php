<?php

require_once __DIR__ . '/../vendor/autoload.php';

\App\Facades\Env::safeLoad(__DIR__ . '/..', '.env.testing');
