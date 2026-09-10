<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CBT REST API Routes
|--------------------------------------------------------------------------
|
| All routes in this file are automatically prefixed with "/api" by Laravel.
| Version 1 routes are mounted at "/api/v1".
|
*/

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
