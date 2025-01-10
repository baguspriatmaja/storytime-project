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


Route::apiResource('/stories', StoryController::class, ['only' => ['index', 'show']]);
Route::apiResource('/category', CategoryController::class, ['only' => ['index', 'show']]);
Route::get('/stories/category/{categoryId}', [StoryController::class, 'getStoriesByCategory']);
Route::get('/get/latest-stories', [StoryController::class, 'getLatestStory']);
Route::get('/get/newest-stories', [StoryController::class, 'getNewestStory']);


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/stories', StoryController::class, ['except' => ['index', 'show']]);
    Route::apiResource('/category', CategoryController::class, ['except' => ['index', 'show']]);
    Route::apiResource('/bookmark', App\Http\Controllers\BookmarkController::class);
});





