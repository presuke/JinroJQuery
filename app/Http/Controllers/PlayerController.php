<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
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
