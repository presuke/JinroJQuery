<?php

namespace App\Http\Controllers;

use App\Consts\ActionConst;
use App\Consts\TimezoneConst;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Player;
use App\Models\Room;
use App\Models\History;
use App\Consts\RoleConst;

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

    public function getRoomStatus()
    {
        $ret = [];
        $param = $_POST['param'];

        $ret['serverTime'] = date('H:i:s');
        try {
            //roomの情報
            $filter = ['name' => $param['room']];
            $room = Room::where($filter)->first();
            $ret['room'] = $room;

            //playerの情報
            $filter = ['room_name' => $param['room']];
            $recs = Player::select('name', 'icon', 'date', 'time_zone', 'killed')->where($filter)->get();
            $players = [];
            foreach($recs as $rec){
                $players[$rec['name']] = $rec;
            }
            $ret['players'] = $players;

            //自分のhistoryの情報
            $filter = ['room_name' => $param['room'],
                       'player_name' => $param['player']];
            $ownHistorys = History::select('room_name', 'player_name', 'date', 'time_zone', 'action', 'target')->where($filter)->get();
            $ret['ownHistorys'] = $ownHistorys;

            //TimeZoneによってゲーム判定を変える
            switch($room['time_zone']){
                //夕刻　投票中状況を確認
                case TimezoneConst::EVENING:
                    $this->checkStatusVote($ret);
                    break;

                //夜　投票結果発表
                case TimezoneConst::NIGHT:
                    $this->checkGoRoomAllPlayer($ret);
                    break;

                //深夜
                case TimezoneConst::MIDNIGHT:
                    $this->checkAttackResult($ret);
                    break;
                //朝　全てのプレーヤーが結果確認したかを確認
                case TimezoneConst::MORNING:
                    $this->checkGoHallAllPlayer($ret);
                    break;
            }
        } catch(Exception $ex) {
            $ret['error'] = $ex;
        }
        return response()->json($ret);
    }

    private function checkStatusVote(&$ret){
        $room = $ret['room'];
        $players = $ret['players'];

        //生きている人の数
        $cntPlayerLive = 0;
        foreach($players as $player){
            if($player['killed'] == ""){
                $cntPlayerLive++;
            }
        }

        //historyの情報
        $filter = ['room_name' => $room['name'],
                   'date' => $room['date'],
                   'time_zone' => $room['time_zone'],
                   'action' => 'vote'];
        $recs = History::select('room_name', 'player_name', 'date', 'time_zone', 'action', 'target')->where($filter)->get();
        $historys = [];
        foreach ($recs as $rec) {
            $historys[$rec['player_name']] = $rec;
        }
        $ret['historys'] = $historys;

        //全員投票済みであれば、投票結果をもとにプレイヤーを退場させて、時を進める
        if($cntPlayerLive == count($historys)){
            //集計
            $voteds = [];
            $cntTop = 0;
            foreach($historys as $player => $history){
                $votedPlayer = $history['target'];
                //投票されたプレイヤーの名前
                if(isset($voteds[$votedPlayer]))
                    $voteds[$votedPlayer]++;
                else
                    $voteds[$votedPlayer] = 1;

                //得票数トップを更新
                if($cntTop < $voteds[$votedPlayer])
                    $cntTop = $voteds[$votedPlayer];
            }

            //単独トップが特定できるか？
            $top = [];
            foreach($voteds as $votedPlayer => $cnt){
                if($cntTop == $cnt){
                    $top[] = $votedPlayer;
                }
            }

            DB::beginTransaction();

            //単独トップなら退場
            $votedResult = [];
            if(count($top) == 1){
                //退場者が出た場合
                $votedPlayer = $top[0];
                $votedResult['kill'] = $votedPlayer;
                $ret['kill'] = $votedPlayer;
                //投票で決まったプレイヤーを退場させる
                $filter = ['room_name' => $room['name'],
                    'name' => $votedPlayer];
                Player::where($filter)->update([
                    'killed' => 'vote',
                ]);
            }else{
                $votedResult['kill'] = "";
                $votedResult['msg'] = count($top) . "人が同列一位だったため投票は無効となりました。";
                $ret['kill'] = "";
                $ret['msg'] = count($top) . "人が同列一位だったため投票は無効となりました。";
            }

            //人狼と村人の数を確認する
            $cntJinro = 0;
            $cntMurabito = 0;

            $filter = ['room_name' => $room['name'],
                'killed' => ''];
            $recs = Player::select('role')->where($filter)->get();

            foreach($recs as $rec) {
                if($rec['role'] == RoleConst::JINRO)
                    $cntJinro++;
                else
                    $cntMurabito++;
            }

            $filter = ['name' => $room['name']];
            if($cntJinro == 0){
                //村人の勝ち
                Room::where($filter)->update(['voted' => json_encode($votedResult, JSON_UNESCAPED_UNICODE),
                    'winner' => RoleConst::MURABITO]);
            }else if($cntJinro >= $cntMurabito){
                //人狼の勝ち
                Room::where($filter)->update(['voted' => json_encode($votedResult, JSON_UNESCAPED_UNICODE),
                    'winner' => RoleConst::JINRO]);
            }else{
                //決着つかず、タイムゾーンを夜に進める
                Room::where($filter)->update(['voted' => json_encode($votedResult, JSON_UNESCAPED_UNICODE),
                    'time_zone' => TimezoneConst::NIGHT]);
            }
            DB::commit();

            //roomの情報を再取得
            $room = Room::where($filter)->first();
            $ret['room'] = $room;
        }
    }

    private function checkGoRoomAllPlayer(&$ret){
        $room = $ret['room'];
        $players = $ret['players'];

        //全てのプレイヤーが部屋に戻っているか確認する
        $isAllPlayerMyRoom = true;
        foreach($players as $player){
            //死んでないプレイヤーのタイムゾーンが2でなければ全員戻っていない
            if($player['killed'] == "" &&
                $player['time_zone'] != TimezoneConst::MIDNIGHT){
                $isAllPlayerMyRoom = false;
            }
        }
        if($isAllPlayerMyRoom){
            $filter = ['name' => $room['name']];
            Room::where($filter)->update([
                'time_zone' => 2,
            ]);
        }
    }

    private function checkAttackResult(&$ret){
        $room = $ret['room'];
        $players = $ret['players'];

        //historyから現在日を取得
        $filter = ['room_name' => $room['name']];
        $currentDate = History::where($filter)->max('date');

        $cntLive = 0;
        $cntDone = 0;
        foreach($players as $player){
            if($player['killed'] == ""){
                $cntLive++;
                if($player['date'] == $currentDate &&
                    $player['time_zone'] == TimezoneConst::MORNING){
                    $cntDone++;
                }
            }
        }

        //全ての人が行動を終えていたら結果判定
        if($cntLive == $cntDone){
            $filter = ['room_name' => $room['name'],
                'date' => $currentDate,
                'time_zone' => 2];
            $recs = History::select('room_name', 'player_name', 'date', 'time_zone', 'action', 'target')->where($filter)->get();
            $historys = [];
            foreach ($recs as $rec) {
                $historys[$rec['player_name']] = $rec;
            }

            //襲撃状況を整理
            $attacked = [];
            foreach($historys as $player => $history){
                if($history['action'] == ActionConst::ATTACK)
                    $attacked[$history['target']] = $player;
            }

            //防御状況を整理
            $saved =  [];
            foreach($historys as $player => $history){
                if($history['action'] == ActionConst::DEFENSE){
                    if(isset($attacked[$history['target']])){
                        unset($attacked[$history['target']]);
                        $saved[] = $history['target'];
                    }
                }
            }

            $resultAttacked = [];
            $resultAttacked['saved'] = $saved;

            try {
                DB::beginTransaction();

                //人狼によって退場させられた人のKillステータスを変更
                $resultAttacked['killed'] = [];
                foreach($attacked as $killed => $kills){
                    $resultAttacked['killed'][] = $killed;
                    $filter = ['room_name' => $room['name'],
                        'name' => $killed];
                    Player::where($filter)->update([
                        'killed' => $kills,
                    ]);
                }

                //人狼と村人の数を比較して、人狼が同数以上なら人狼の勝利
                $filter = ['room_name' => $room['name'],
                    'killed' => ''];
                $recs = Player::select('role')->where($filter)->get();
                $cntJinro = 0;
                $cntMurabito = 0;
                foreach($recs as $rec){
                    if($rec['role'] == RoleConst::JINRO)
                        $cntJinro++;
                    else
                        $cntMurabito++;
                }

                //部屋情報更新
                $winner = ($cntJinro >= $cntMurabito) ? RoleConst::JINRO : '';
                $filter = ['name' => $room['name']];
                Room::where($filter)->update([
                    'date' => ($currentDate + 1),
                    'time_zone' => 3,
                    'killed' => json_encode($resultAttacked),
                    'winner' => $winner,
                ]);

                //プレイヤーの日付変更
                $filter = ['room_name' => $room['name'],
                    'killed' => ''];
                Player::where($filter)->update([
                    'date' => ($currentDate + 1),
                ]);

                DB::commit();
            } catch(Exception $ex) {
                $ret['error'] = $ex;
                DB::rollBack();
            }
        }
    }

    private function checkGoHallAllPlayer(&$ret){
        $room = $ret['room'];
        $players = $ret['players'];

        //全てのプレイヤーが部屋に戻っているか確認する
        $isAllPlayerHall = true;
        foreach($players as $player){
            //死んでないプレイヤーのタイムゾーンが2でなければ全員戻っていない
            if($player['killed'] == "" &&
                $player['time_zone'] != TimezoneConst::EVENING){
                $isAllPlayerHall = false;
            }
        }
        if($isAllPlayerHall){
            $filter = ['name' => $room['name']];
            Room::where($filter)->update([
                'time_zone' => TimezoneConst::EVENING,
                'voted' => '',
                'killed' => '',
            ]);
        }

    }
}
