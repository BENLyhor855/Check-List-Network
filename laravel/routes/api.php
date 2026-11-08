<?php

use App\Http\Controllers\NetworkController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('workspace'));
Route::get('/api/v1/checks', [\App\Http\Controllers\DailyCheckController::class, 'index']);
Route::post('/api/v1/checks', [\App\Http\Controllers\DailyCheckController::class, 'save']);
Route::get('/health/live', [NetworkController::class, 'live']);
Route::get('/health/ready', [NetworkController::class, 'ready']);
Route::post('/api/v1/auth/login', [NetworkController::class, 'login'])->middleware('throttle:10,1');
Route::post('/api/v1/auth/logout', [NetworkController::class, 'logout']);
Route::get('/api/v1/auth/me', [NetworkController::class, 'me']);
Route::get('/api/v1/users', [NetworkController::class, 'users']);
Route::post('/api/v1/users', [NetworkController::class, 'users']);
Route::patch('/api/v1/users/{id}', [NetworkController::class, 'users']);
Route::match(['get', 'post'], '/api/v1/{resource}', [NetworkController::class, 'inventory'])->whereIn('resource', ['buildings', 'locations', 'devices', 'audit']);
