<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\CoursesController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::middleware('guest.api')->group(function () {
    Route::post('/login',[LoginController::class,'login']); 
    Route::post('/register',[LoginController::class,'register']); 
});


Route::middleware('auth:api')->group( function () {

    Route::get('/logout',[LoginController::class,'logout']); 
    Route::get('/courses',[CoursesController::class,'getCourses']); 
    Route::get('/courses/{id}',[CoursesController::class,'getCourse']); 
    Route::post('/courses', [CoursesController::class,'storeCourse']);
    Route::delete('/courses/{id}', [CoursesController::class, 'courseDelete']);
    Route::put('/courses/{id}', [CoursesController::class, 'courseUpdate']);       // full update
    Route::patch('/courses/{id}', [CoursesController::class, 'coursePartialUpdate']); // partial update
    // Route::get('/courses',[CoursesController::class,'getCourses'])->middleware('throttle:15,1')->name('get.courses');
});