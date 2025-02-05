<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BookmarkController;
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

// Route Auth
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);


// Route User
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{userId}/update', [UserController::class, 'updateProfile']);
    Route::post('/users/{userId}/image', [UserController::class, 'updateProfileImage']);
    Route::get('/users/get', [UserController::class, 'getUsers']);
    Route::get('/users/auth', [UserController::class, 'getAuthUser']);
});

// Route Story
Route::get('/stories', [StoryController::class, 'index']);
Route::get('/stories/{storyId}', [StoryController::class, 'show']);
Route::get('/get/latest-stories', [StoryController::class, 'getLatestStory']);
// Route::get('/get/newest-stories', [StoryController::class, 'getNewestStory']);
Route::get('/stories/{id}/images', [StoryController::class, 'getImagesByStoryId']);
// Route::get('/ascending', [StoryController::class, 'getStoriesAscending']);
// Route::get('/descending', [StoryController::class, 'getStoriesDescending']);
Route::get('/sort-by', [StoryController::class, 'getStories']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-stories', [StoryController::class, 'getMyStories']);
    Route::post('/stories/add', [StoryController::class, 'store']);
    Route::post('/stories/{storyId}/update', [StoryController::class, 'update']);
    Route::delete('/stories/{storyId}/delete', [StoryController::class, 'destroy']);
});


// Route Category
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{categoryId}', [CategoryController::class, 'show']);
Route::get('/get-categories', [CategoryController::class, 'getAllCategoriesWithStories']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories/add', [CategoryController::class, 'store']);
    Route::put('/categories/{categoryId}/update', [CategoryController::class, 'update']);
    Route::delete('/categories/{categoryId}/delete', [CategoryController::class, 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/bookmarks', BookmarkController::class)->except(['show','destroy']);
    Route::get('/bookmarks/{id}', [BookmarkController::class, 'show']);
    Route::get('/my-bookmarks', [BookmarkController::class, 'getMyBookmarks']);
    Route::delete('/bookmarks/{id}', [BookmarkController::class, 'destroy']);
});






