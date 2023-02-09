<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Consts\ActionConst;
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
    Route::post('/makeRoom', [App\Http\Controllers\RoomController::Class, 'makeRoom']);
    Route::post('/getRooms', [App\Http\Controllers\RoomController::Class, 'getRooms']);
    Route::post('/getRoomStatus', [App\Http\Controllers\RoomController::Class, 'getRoomStatus']);

    Route::post('/getPlayers', [App\Http\Controllers\PlayerController::Class, 'getPlayers']);
    Route::post('/getPlayerInfo', [App\Http\Controllers\PlayerController::Class, 'getPlayerInfo']);
    Route::post('/initPlayer', [App\Http\Controllers\PlayerController::Class, 'initPlayer']);

    Route::post('/action_'.ActionConst::VOTE, [App\Http\Controllers\ActionController::Class, 'vote']);
    Route::post('/action_'.ActionConst::DEFENSE, [App\Http\Controllers\ActionController::Class, 'defense']);
    Route::post('/action_'.ActionConst::ATTACK, [App\Http\Controllers\ActionController::Class, 'attack']);
    Route::post('/action_'.ActionConst::PSYCHIC, [App\Http\Controllers\ActionController::Class, 'psychic']);
    Route::post('/action_'.ActionConst::SLEEP, [App\Http\Controllers\ActionController::Class, 'sleep']);
    Route::post('/action_'.ActionConst::GOMYROOM, [App\Http\Controllers\ActionController::Class, 'go_myroom']);
    Route::post('/action_'.ActionConst::GOHALL, [App\Http\Controllers\ActionController::Class, 'go_hall']);
    Route::post('/action_'.ActionConst::VOTERESULT_CONFIRMED, [App\Http\Controllers\ActionController::Class, 'voteresult_confirmed']);
    Route::post('/action_'.ActionConst::ATTACK_RESULT_CONFIRMED, [App\Http\Controllers\ActionController::Class, 'attackresult_confirmed']);

});
