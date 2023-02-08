<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Player;
use App\Models\History;

class TestController extends Controller
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

    public function getPlayers()
    {
        $ret = [];
        $param = $_POST['param'];
        $filter = ['room_name' => $param['room']];
        $players = Player::select('name')->where($filter)->get();
        $ret['room'] = $param['room'];
        $ret['players'] = $players;
        return response()->json($ret);
    }


    public function getPlayerInfo()
    {
        $param = $_POST['param'];
        $ret = [];

        $filter = ['room_name' => $param['room'], 'name' => $param['player']];
        $info = Player::where($filter)->first();

        //もし人狼であれば、仲間を教える
        if(isset($info['Role'])){
            if($info['Role'] == 'jinro'){
                $filter = ['room_name' => $param['room'], 'role' => 'jinro'];
                $info['jinrolist'] = Player::where($filter)->get();
            }
        }

        $filter = ['room_name' => $param['room']];
        $players = Player::where($filter)->get();

        $cntPlayerTotal = count($players->all());
        $cntPlayerEntry = 0;
        foreach ($players->all() as $player){
            if($player['pass'] != ""){
                $cntPlayerEntry++;
            }
        }
        $info['playerTotal'] = $cntPlayerTotal;
        $info['playerEntry'] = $cntPlayerEntry;
        $ret['info'] = $info;

        return response()->json($ret);
    }
}
