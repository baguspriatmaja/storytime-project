<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\StoryController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{userId}/update', [UserController::class, 'updateProfile']);
    Route::post('/users/{userId}/image', [UserController::class, 'updateProfileImage']);
    Route::get('/users/get', [UserController::class, 'getUsers']);
    Route::get('/users/auth', [UserController::class, 'getAuthUser']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/stories', App\Http\Controllers\StoryController::class);
    Route::apiResource('/category', App\Http\Controllers\CategoryController::class);
    Route::apiResource('/bookmark', App\Http\Controllers\BookmarkController::class);
    Route::get('/stories/category/{categoryId}', [StoryController::class, 'getByCategory']);
});



