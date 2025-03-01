<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use \App\Http\Controllers\LikeController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::apiResource('blogs' , BlogController::class);
Route::apiResource('categories' , CategoryController::class);
Route::post('/blogs/search', [BlogController::class, 'search']);
Route::post('/blogs/upload', [BlogController::class, 'uploadImage']);
Route::apiResource('users' , UserController::class);
Route::get('/users/search', [UserController::class, 'search']);
Route::post('/users/lock/{id}', [UserController::class, 'lock']);
Route::post('/users/{user}/image', [UserController::class, 'uploadProfileImage']);
Route::post('/users/{user}/password', [UserController::class, 'updatePassword']);
Route::apiResource('blogs.comments', CommentController::class);
Route::apiResource('blogs.likes' , LikeController::class);


//Auth Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});
