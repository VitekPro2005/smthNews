<?php

use App\Http\Controllers\Api\NewsApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/news/{page}/{limit}', [NewsApiController::class, 'index'])
    ->where(['page' => '[0-9]+', 'limit' => '[0-9]+']);
