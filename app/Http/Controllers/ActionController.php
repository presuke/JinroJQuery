<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Player;
use App\Models\Room;
use App\Consts\ActionConst;
use App\Consts\TimezoneConst;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActionController extends Controller
{
    public function vote()
    {
        $ret = [];
        $param = $_POST['param'];

        //roomの情報
        $filter = ['name' => $param['room']];
        $room = Room::where($filter)->first();
        $ret['room'] = $room;

        try {
            DB::beginTransaction();

            $filter = ['room_name' => $param['room'],
                'player_name' => $param['player'],
                'date' => $room['date'],
                'time_zone' => TimezoneConst::EVENING];
            $existsDuplicateRecord = History::where($filter)->get()->count() > 0;

            if(!$existsDuplicateRecord) {
                $history = new History();
                $history->create([
                    'room_name' => $param['room'],
                    'player_name' => $param['player'],
                    'date' => $room['date'],
                    'time_zone' => TimezoneConst::EVENING,
                    'action' => ActionConst::VOTE,
                    'target' => $param['select'],
                ]);
            }

            $filter = ['room_name' => $param['room'],
                       'name' => $param['player']];
            Player::where($filter)->update([
                'time_zone' => TimezoneConst::NIGHT,
            ]);
            DB::commit();

            $ret['msg'] = "投票完了しました";
        } catch(Exception $ex) {
            $ret['error'] = $ex;
            DB::rollBack();
        }
        return response()->json($ret);
    }

    public function attack(){
        $ret = [];
        $param = $_POST['param'];

        //roomの情報
        $filter = ['name' => $param['room']];
        $room = Room::where($filter)->first();
        try {
            DB::beginTransaction();

            $filter = ['room_name' => $param['room'],
                'player_name' => $param['player'],
                'date' => $room['date'],
                'time_zone' => TimezoneConst::MIDNIGHT];
            $existsDuplicateRecord = History::where($filter)->get()->count() > 0;

            if(!$existsDuplicateRecord) {
                $history = new History();
                $history->create([
                    'room_name' => $param['room'],
                    'player_name' => $param['player'],
                    'date' => $room['date'],
                    'time_zone' => TimezoneConst::MIDNIGHT,
                    'action' => ActionConst::ATTACK,
                    'target' => $param['select'],
                ]);
            }

            $filter = ['room_name' => $param['room'],
                'name' => $param['player']];
            Player::where($filter)->update([
                'time_zone' => TimezoneConst::MORNING,
            ]);
            DB::commit();

            $ret['msg'] = "襲撃完了しました";
        } catch(Exception $ex) {
            $ret['error'] = $ex;
            DB::rollBack();
        }
        return response()->json($ret);
    }

    public function defense(){
        $ret = [];
        $param = $_POST['param'];

        //roomの情報
        $filter = ['name' => $param['room']];
        $room = Room::where($filter)->first();
        try {
            DB::beginTransaction();

            $history = new History();
            $history->create([
                'room_name' => $param['room'],
                'player_name' => $param['player'],
                'date' => $room['date'],
                'time_zone' => TimezoneConst::MIDNIGHT,
                'action' => ActionConst::DEFENSE,
                'target' => $param['select'],
            ]);

            $filter = ['room_name' => $param['room'],
                'name' => $param['player']];
            Player::where($filter)->update([
                'time_zone' => TimezoneConst::MORNING,
            ]);
            DB::commit();

            $ret['msg'] = "襲撃完了しました";
        } catch(Exception $ex) {
            $ret['error'] = $ex;
            DB::rollBack();
        }
        return response()->json($ret);
    }

    public function voteresult_confirmed()
    {
        $ret = [];
        $param = $_POST['param'];

        //roomの情報
        $filter = ['name' => $param['room']];
        $room = Room::where($filter)->first();
        $ret['room'] = $room;

        try {
            $filter = ['room_name' => $param['room'],
                'player_name' => $param['player'],
                'date' => $room['date'],
                'time_zone' => TimezoneConst::NIGHT,
                'action' => ActionConst::VOTERESULT_CONFIRMED];
            $existsDuplicateRecord = History::where($filter)->get()->count() > 0;

            if(!$existsDuplicateRecord){
                $history = new History();
                $history->create([
                    'room_name' => $param['room'],
                    'player_name' => $param['player'],
                    'date' => $room['date'],
                    'time_zone' => TimezoneConst::NIGHT,
                    'action' => ActionConst::VOTERESULT_CONFIRMED,
                    'target' => '',
                ]);
            }
            $ret['msg'] = "投票結果確認完了しました。";
        } catch(Exception $ex) {
            $ret['error'] = $ex;
        }
        return response()->json($ret);
    }

    public function attackresult_confirmed()
    {
        $ret = [];
        $param = $_POST['param'];

        //roomの情報
        $filter = ['name' => $param['room']];
        $room = Room::where($filter)->first();
        $ret['room'] = $room;

        try {
            $filter = ['room_name' => $param['room'],
                'player_name' => $param['player'],
                'date' => $room['date'],
                'time_zone' => TimezoneConst::MORNING,
                'action' => ActionConst::VOTERESULT_CONFIRMED];
            $existsDuplicateRecord = History::where($filter)->get()->count() > 0;

            if(!$existsDuplicateRecord){
                $history = new History();
                $history->create([
                    'room_name' => $param['room'],
                    'player_name' => $param['player'],
                    'date' => $room['date'],
                    'time_zone' => TimezoneConst::MORNING,
                    'action' => ActionConst::ATTACK_RESULT_CONFIRMED,
                    'target' => '',
                ]);
            }
            $ret['msg'] = "襲撃結果確認完了しました。";
        } catch(Exception $ex) {
            $ret['error'] = $ex;
        }
        return response()->json($ret);
    }

    public function go_myroom(){
        $ret = [];
        $param = $_POST['param'];
        $filter = ['room_name' => $param['room'], 'name' => $param['player']];
        Player::where($filter)->update(['time_zone' => TimezoneConst::MIDNIGHT]);
        $ret['msg'] = "自室に戻りました。";
        return response()->json($ret);
    }

    public function sleep(){
        $ret = [];
        $param = $_POST['param'];
        $filter = ['room_name' => $param['room'], 'name' => $param['player']];
        Player::where($filter)->update(['time_zone' => TimezoneConst::MORNING]);
        $ret['msg'] = "就寝しました。";
        return response()->json($ret);
    }

    public function go_hall(){
        $ret = [];
        $param = $_POST['param'];
        $filter = ['room_name' => $param['room'], 'name' => $param['player']];
        Player::where($filter)->update(['time_zone' => TimezoneConst::EVENING]);
        $ret['msg'] = "ホールへ移動しました。";
        return response()->json($ret);
    }
}
