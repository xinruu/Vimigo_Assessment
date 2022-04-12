<?php

use App\Http\Controllers\API\PassportAuthController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    Route::get('get-user', [PassportAuthController::class, 'userInfo']);
});

Route::resource('users', UserController::class);

// Filter multiple fields (Optional parameter)
Route::get('/users/{name?}/{email?}', [UserController::class, 'filter']);

// import file
Route::post('/upload-content',[UserController::class,'uploadContent'])->name('import.content');