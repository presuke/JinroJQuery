<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['middleware' => 'api'])->group(function(){
    Route::post('/makeRoom', [App\Http\Controllers\TestController::Class, 'makeRoom']);
    Route::post('/getRooms', [App\Http\Controllers\TestController::Class, 'getRooms']);
    Route::post('/getPlayers', [App\Http\Controllers\TestController::Class, 'getPlayers']);
    Route::post('/getPlayerInfo', [App\Http\Controllers\TestController::Class, 'getPlayerInfo']);
});
