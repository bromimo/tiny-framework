<?php

use Dotenv\Dotenv;
use TinyRouter\Facade\Route;
use TinyRouter\Http\Request;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

Route::dispatch(Request::fromGlobals())->send();
