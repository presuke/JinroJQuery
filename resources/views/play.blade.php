<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Jinro</title>

    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
    <script type="text/javascript" src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.6.4/jquery.colorbox-min.js"></script>
    <script type="text/javascript" src="js/play.js"></script>
    <link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/dark-hive/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.min.css">
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.css">
    <link rel="stylesheet" type="text/css" href="css/play.css" />


    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
        }
    </style>
</head>
<body class="antialiased">
<div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center py-4 sm:pt-0">
    @if (Route::has('login'))
        <div class="hidden fixed top-0 right-0 px-6 py-4 sm:block">
            @auth
                <a href="{{ url('/home') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Home</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Log in</a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline">Register</a>
                @endif
            @endauth
        </div>
    @endif

        <input id="status" type="hidden" value="{}" />
        <div id="main">
            <header>
                <div id="menu"></div>
            </header>
            <div id="message">
                <div id="messageBody">aaaaaaaaaaaaaaaaaaaa</div>
                <div style="text-align:center;">
                    <div id="messageClose" class="btn-square-pop" style="margin: 0 auto; bottom:5px;">OK</div>
                </div>
            </div>
            <div id="initialBox">
                <div>
                    <div>
                        <span class="myName"></span>さん、初期設定をしてください。<br>
                        初期設定後、あなたの正体が表示されます。<br>
                    </div>
                    <br>
                    <div id="setAvater" style="z-index:1;">
                        <div style="text-align:left;">
                            アバターを指定してください。
                        </div>
                        <div id="changeSex" class="btn-square-pop" data-sex="m" style="z-index:0;">アバターの性別切替</div>
                        <br style="clear:left;">
                        <div class="avater" data-sex="m" style="">
                            <div data-index="01" style="background-image: url('image/avatar/m/shot01.png'"></div>
                            <div data-index="02" style="background-image: url('image/avatar/m/shot02.png'"></div>
                            <div data-index="03" style="background-image: url('image/avatar/m/shot03.png'"></div>
                            <div data-index="04" style="background-image: url('image/avatar/m/shot04.png'"></div>
                            <div data-index="05" style="background-image: url('image/avatar/m/shot05.png'"></div>
                            <div data-index="06" style="background-image: url('image/avatar/m/shot06.png'"></div>
                            <div data-index="07" style="background-image: url('image/avatar/m/shot07.png'"></div>
                            <div data-index="08" style="background-image: url('image/avatar/m/shot08.png'"></div>
                            <div data-index="09" style="background-image: url('image/avatar/m/shot09.png'"></div>
                            <div data-index="10" style="background-image: url('image/avatar/m/shot10.png'"></div>
                            <div data-index="11" style="background-image: url('image/avatar/m/shot11.png'"></div>
                            <div data-index="12" style="background-image: url('image/avatar/m/shot12.png'"></div>
                        </div>
                        <div class="avater" data-sex="f" style="margin-left:-10000;">
                            <div data-index="01" style="background-image: url('image/avatar/f/shot01.png'"></div>
                            <div data-index="02" style="background-image: url('image/avatar/f/shot02.png'"></div>
                            <div data-index="03" style="background-image: url('image/avatar/f/shot03.png'"></div>
                            <div data-index="04" style="background-image: url('image/avatar/f/shot04.png'"></div>
                            <div data-index="05" style="background-image: url('image/avatar/f/shot05.png'"></div>
                            <div data-index="06" style="background-image: url('image/avatar/f/shot06.png'"></div>
                            <div data-index="07" style="background-image: url('image/avatar/f/shot07.png'"></div>
                            <div data-index="08" style="background-image: url('image/avatar/f/shot08.png'"></div>
                            <div data-index="09" style="background-image: url('image/avatar/f/shot09.png'"></div>
                            <div data-index="10" style="background-image: url('image/avatar/f/shot10.png'"></div>
                            <div data-index="11" style="background-image: url('image/avatar/f/shot11.png'"></div>
                            <div data-index="12" style="background-image: url('image/avatar/f/shot12.png'"></div>
                        </div>
                    </div>
                    <div id="setPassCode">
                        <div style="text-align:left;">
                            パスコードを指定してください。
                        </div>
                        <div class="tenKey"></div>
                        <br style="clear:left;" />
                        <div id="setPlayerProfile" class="btn-square-pop" style="font-size:larger; margin: 10 auto;">コレデキマリ</div>
                    </div>
                    <br style="clear:left;" />
                </div>
            </div>
            <div id="overlay">
                <img src="image/loading.gif" style="width:200px; 200px;" />
            </div>

            <div id="information">
                <div id="timeZone"></div>
                <div id="informationBody"></div>
            </div>
            <div id="statusBox">
                <div id="statusGraphic"></div>
                <div id="statusText"></div>
            </div>

            <div id="auth" style="display:none;" title="認証">
                あなたが<span class="myName"></span>さんご本人であることを証明するために、パスコードを入力してください。<br>
                <br>
                <div class="tenKey"></div>
                <div style="text-align:center;">
                    <div class="btnOK btn-square-pop" style="font-size:larger;">認証</div>
                    <div class="btnCancel btn-square-pop" style="font-size:larger;">キャンセル</div>
                </div>
            </div>

            <div class="backGroundDesign" data-index=0 style="background-image:url('image/evening.jpg');"></div>
            <div class="backGroundDesign" data-index=1 style="background-image:url('image/night.jpg'); z-index: -2;"></div>
            <div class="backGroundDesign" data-index=2 style="background-image:url('image/midnight.jpg'); z-index: -3;"></div>
            <div class="backGroundDesign" data-index=3 style="background-image:url('image/morning.jpg'); z-index: -4;"></div>

            <footer>
                <div style="text-align:right;font-size:8px;padding-right:10px;">
                    Powered by <span style='color:red'>R</span>eno System Development
                </div>
        </div>
        </footer>

</div>

<!-- controlers -->

<div id="dialogMakeRoom" style="display:none;" title="部屋の作成">
    <input type="hidden" id="dialogMakeRoomParam" value='{"roles":{"murabito":1, "jinro":1, "yojinbo": 0, "rebaishi": 0}, "roomName":"", "cnt":0, "players":[]}' />
    <div id="dialogMakeRoomBody"></div>
    <div id="dialogMakeRoomErr" style="color:red;"></div>
</div>

<form id="makeRoomStep1" style="display:none;" title="部屋の作成">
    <fieldset>
        <legend>部屋名</legend>
        部屋名を設定してください。<br>
        ※スペース（前半角とも）は除去されます<br>
        <div style="border-bottom: thin solid #999; width:380px; border-radius:10px; margin:5px; padding:3px; background-color:#fff;">
            <div style='width:360px;float:left;'>
                <input type="text" data-type="roomName" name="roomName" style="ime-mode: disabled; border:none; border-bottom: thin solid #ccc; width:360px;" value="" />
            </div>
            <br style='clear:left;' />
    </fieldset>
    <fieldset>
        <legend>役割</legend>
        各役割の人数を設定してください。
        <br style='clear:left;' />
        <div style="border-bottom: thin solid #999; width:150px; border-radius:10px; margin:5px; padding:3px; background-color:#fff; color:#333; float:left;">
            <div style="width:80px; float:left;">村人</div>
            <div style="width:40px; float:left; border-bottom: thin solid #ccc;">
                <select data-type="input" name="murabito">
                    <option value="1" selected="selected">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                </select>
            </div>
            <div style="width:10px;float:left;">人</div>
            <br style="clear:left;" />
        </div>
        <div style="border-bottom: thin solid #999; width:150px; border-radius:10px; margin:5px; padding:3px; background-color:#fff; color:#333; float:left;">
            <div style="width:80px; float:left;">人狼</div>
            <div style="width:40px; float:left; border-bottom: thin solid #ccc;">
                <select data-type="input" name="jinro">
                    <option value="1" selected="selected">1</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div style="width:10px;float:left;">人</div>
            <br style="clear:left;" />
        </div>
        <div style="border-bottom: thin solid #999; width:150px; border-radius:10px; margin:5px; padding:3px; background-color:#fff; color:#333; float:left; clear:left;">
            <div style="width:80px;float:left;">用心棒</div>
            <div style="width:40px;float:left; border-bottom: thin solid #ccc;">
                <select data-type="input" name="yojinbo">
                    <option value="0" selected="selected">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div style="width:10px;float:left;">人</div>
            <br style="clear:left;" />
        </div>
        <div style="border-bottom: thin solid #999; width:150px; border-radius:10px; margin:5px; padding:3px; background-color:#fff; color:#333; float:left;">
            <div style="width:80px;float:left;">霊媒師</div>
            <div style="width:40px;float:left; border-bottom: thin solid #ccc;">
                <select data-type="input" name="rebaishi">
                    <option value="0" selected="selected">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div style="width:10px;float:left;">人</div>
            <br style="clear:left;" />
        </div>
        <br style='clear:left;' />
    </fieldset>
    <div style="width:100%; text-aling:center; margin-top:5vh;">
        <div style="width:20%; margin: 0 auto;">
            <a href="javascript:makeRoomStep(2);" class="btn-square-pop" style="font-size:larger;">次へ</a>
        </div>
    </div>
</form>

<form name="player" id="makeRoomStep2" style="display:none;" title="部屋の作成">
    <div style="border-bottom: thin solid #999; width:420px; border-radius:10px; margin:5px; padding:3px; background-color:#fff;">
        <div style='width:120px; float:left;'>プレイヤー<span class="playerIndex"></span></div>
        <div style='width:280px; float:left;'>
            <input type="text" data-type="playerName" name="" style="ime-mode: disabled; border:none; border-bottom: thin solid #ccc; width:260px;" value="" />
        </div>
        <br style='clear:left;' />
    </div>
</form>

<form id="dialogChoiceRoom" style="display:none;" title="部屋の選択">
    <div id="choiceRoomBody"></div>
    <br style="clear:left;">
    <div id="edit_license_msg" style="font-size:smaller;"></div>
</form>

<div id="tenKey" style="display:none;" title="テンキー">
    <div style="width:100%;text-align:center;">
        <div class="tenKeyInput"></div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">1</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">2</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">3</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">4</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">5</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">6</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">7</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">8</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">9</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">0</div>
    </div>
    <div style="float:left; margin:5px;">
        <div class="btn-ten-key btn-square-pop">Clear</div>
    </div>
    <br style="clear:left;" />
</div>

<div id="myRole" style="display:none;" title="myRole">
</div>

<div id="dialogAlert"></div>
</div>
</body>
</html>
