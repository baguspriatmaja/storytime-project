<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\CategoryController;
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

Route::get('/stories', [StoryController::class, 'index']);
Route::get('/stories/{id}', [StoryController::class, 'show']);
Route::get('/category', [CategoryController::class, 'index']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::get('/stories/category/{categoryId}', [StoryController::class, 'getByCategory']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/stories', [StoryController::class, 'store']);
    Route::post('/stories/{id}', [StoryController::class, 'update']);
    Route::delete('/stories/{id}', [StoryController::class, 'destroy']);
    Route::post('/category', [CategoryController::class, 'store']);
    Route::put('/category/{id}', [CategoryController::class, 'update']);
    Route::delete('/category/{id}', [CategoryController::class, 'destroy']);
    Route::apiResource('/bookmark', App\Http\Controllers\BookmarkController::class);
});





