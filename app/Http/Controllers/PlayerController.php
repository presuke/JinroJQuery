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
        $players = Player::select('name', 'icon')->where($filter)->get();
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


    public function initPlayer(){
        $ret = [];
        $param = $_POST['param'];

        $filter = ['room_name' => $param['room'],
                   'icon' => $param['icon']];
        $isUsedIcon = Player::select('name', 'icon')->where($filter)->get()->count() > 0;

        if($isUsedIcon){
            $ret['error'] = "このアバターアイコンは、既に他のプレイヤーに使われています。別のアバターを選んでください。";
        }else{
            try {
                $filter = ['room_name' => $param['room'],
                           'name' => $param['player']];
                Player::where($filter)->update([
                    'icon' => $param['icon'],
                    'pass' => $param['pass'],
                ]);

                $filter = ['room_name' => $param['room'], 'name' => $param['player']];
                $info = Player::where($filter)->first();
                $ret['info'] = $info;
            } catch(Exception $ex) {
                $ret['error'] = $ex;
            }
        }
        return response()->json($ret);
    }
}
