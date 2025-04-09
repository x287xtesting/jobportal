<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::post('regist', [UserController::class, 'regist']);
Route::post('/verify-email', [UserController::class, 'verifyEmail']);

Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout']);

Route::get('showall', [UserController::class, 'displayAllEvents']);
Route::get('showevent/{event_id}', [UserController::class, 'showEvent']);

Route::post('/addevent', [EventController::class, 'addEvent'])->middleware("jwt.auth");
Route::post('/upevents/{event_id}', [EventController::class, 'updateEvent'])->middleware("jwt.auth");
Route::delete('delevents/{event_id}', [EventController::class, 'deleteEvent'])->middleware("jwt.auth");


Route::post('/book/{event_id}', [BookController::class, 'book'])->middleware('jwt.auth');
