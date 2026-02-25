<?php

use TinyRouter\Facade\Route;
use TinyRouter\Http\Response;

Route::get('/', fn() => new Response('Users API is running.'));
