<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function makeRoom()
    {
        $param = $_POST['param'];
        $ret = [];

        //カードをプレイヤーに配って役割を決定
        $cards = [];
        foreach($param['roles'] as $role => $val){
            for($i=0; $i<$val; $i++){
                $cards[] = $role;
            }
        }
        shuffle($cards);

        $playerRole = [];
        $players = $param['players'];
        if(count($cards) == count($players)){
            $cardIndex = 0;
            foreach($players as $player){
                $playerRole[$player] = $cards[$cardIndex];
                $cardIndex++;
            }
        }
        $ret['roomName'] = $param['roomName'];
        $ret['playerRole'] = $playerRole;

        try {
            DB::beginTransaction();

            DB::table('rooms')->insert([
                'name' => $param['roomName'],
                'voted' => '',
                'killed' => '',
                'winner' => '',
            ]);
            foreach($playerRole as $player => $role){
                DB::table('players')->insert([
                    'room_name' => $param['roomName'],
                    'name' => $player,
                    'role' => $role,
                    'pass' => '',
                    'icon' => '',
                    'killed' => '',
                    'date' => 0,
                    'time_zone' => 0,
                ]);
            }
            DB::commit();

            $ret['msg'] = count($players)."人プレイの部屋「".$param['roomName']."」を作成しました。";
        } catch(Exception $ex) {
            $ret['error'] = $ex;
            DB::rollBack();
        }
        return response()->json($ret);
    }

    public function getRooms()
    {
        $ret = [];

        $rooms = Room::all();
        $ret['rooms'] = $rooms;
        return response()->json($ret);
    }
}
