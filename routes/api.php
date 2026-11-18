<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/filters', [JobController::class, 'filters']);
Route::get('/jobs/{id}', [JobController::class, 'show']);
