<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env for tests
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();
