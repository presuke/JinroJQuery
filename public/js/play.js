let reloadInterval = 5000;
const timeZoneList = ["夕刻", "夜", "深夜", "朝"];
const actionList = ["投票", "自室へ戻り", "行動", "教室へ集合"];

$(document).ready(async function(){
    //UI作成
    try{
        $("#menu").click(function(){
            let jsonStatus = $("#status").val();
            let objStatus = JSON.parse(jsonStatus);
            if(objStatus.info.name != undefined){
                $("#dialogAlert").dialog({
                    title: "選んでください",
                    modal: true,
                    position: {my: "center center" , at: "center center", of: window},
                    show : "fade",
                    hide : "fade",
                    width: $("#main").width() * 0.9,
                    height: $("#main").height() * 0.9,
                    open : function(event, ui){
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(this).html("どうしますか？");
                    },
                    buttons: [
                        {
                            text: "役割を確認する",
                            click: function() {
                                $(this).dialog("close");
                                showRole(false);
                            }
                        },
                        {
                            text: "このアプリについて",
                            click: function(){
                                $(this).dialog("close");
                                dispDialogAbout();
                            }
                        },
                        {
                            text: "閉じる",
                            click: function() {
                                $(this).dialog("close");
                            }
                        }
                    ]
                });
            }else{
                choiceRoom(undefined);
            }
        });

        $(".tenKey").each(function(){
            $(this).html($("#tenKey").html());
        });
        $(".btn-ten-key").click(function(){
            const num = $(this).html();
            $(".tenKeyInput").each(function(){
                if(isNaN(num)){
                    $(this).html("");
                }else if($(this).html().length < 8){
                    $(this).html($(this).html() + "" + num);
                }
            });
        });
        $("#changeSex").click(function(){
            let sex = $(this).data("sex");
            $(".avater[data-sex='" + sex + "']").animate({'marginLeft': -5000}, {duration: 500, queue: false}, 'swing');
            sex = (sex == "m" ? "f" : "m");
            $(this).data("sex", sex);
            $(".avater[data-sex='" + sex + "']").animate({'marginLeft': 10}, {duration: 500, queue: false}, 'swing');
        });
        $(".avater[data-sex='f']").css('marginLeft', -5000);

        $("#setPlayerProfile").click(async function(){
            const sex = $("#changeSex").data("sex");
            let icon = "";
            let isDisabled = false;
            $(".avater[data-sex='" + sex + "'] .slick-active").each(function(){
                isDisabled = $(this).data("disabled") == "disabled";

                icon = $(this).css("background-image");
                icon = icon.split("avatar/")[1];
                icon = icon.split("\")")[0];
            });
            let pass = "";
            $(".tenKeyInput").each(function(){
                pass = $(this).html();
            });
            if(pass == ""){
                await showMessage("パスコードを設定してください");
            }else if(isDisabled){
                await showMessage("このアバターは他のプレイヤーに使われています。別のアバターを選んでください。");
            }else{
                let jsonStatus = $("#status").val();
                let objStatus = JSON.parse(jsonStatus);
                objStatus.icon = icon;
                objStatus.pass = pass;
                $("#status").val( JSON.stringify(objStatus) );

                let obj = await callAPI("initPlayer", $("#status").val());
                if(obj.error != undefined){
                    showMessage(obj.error);
                }else{
                    await showRole(true);
                    $("#setPlayerProfile").slideUp();
                    showMain();
                }
            }
        });
        $("#messageClose").click(function(){
            $("#message").data("isOpen", false);
            $("#message").slideUp();
        });

        $('.avater').slick();
    }catch(error){
    }

    let jsonStatus = $("#status").val();
    let objStatus = JSON.parse(jsonStatus);
    objStatus.room = getParam('room');
    objStatus.player = getParam('player');
    objStatus.isGameSet = false;
    $("#status").val( JSON.stringify(objStatus) );

    try{
        const obj = await callAPI("getPlayerInfo", $("#status").val());

        if(obj.error != undefined)
            throw(new Error("endpointsError:" + JSON.stringify(obj.error)));

        objStatus.info = obj.info;
        $("#status").val( JSON.stringify(objStatus) );

        $("#overlay").html("");

        if(objStatus.info.name == undefined){
            $("#initialBox").hide();
            choiceRoom(null);
        }else{
            $(".myName").html(objStatus.info.name);

            if(objStatus.info.pass == ""){
                $("#overlay").fadeOut();

                const obj = await callAPI("getPlayers", $("#status").val());
                if(obj.players != undefined){
                    obj.players.forEach(function(player) {
                        $(".avater").each(function() {
                            const avaterIcon = $(this).css("background-image");
                            if(avaterIcon.indexOf(player.icon) >= 0) {
                                $(this).css("filter", "grayscale(100%)");
                                $(this).data("disabled", true);
                            }
                        });
                    });
                }
            }else{
                $("#initialBox").fadeOut();
                $(".backGroundDesign").fadeOut();
                while(true){
                    let flg = await auth();
                    if(flg){
                        showMain();
                        break;
                    }
                }
            }
        }
    }catch(ex){
        showError(ex);
    }
});

//メイン画面表示
async function showMain(){
    let jsonStatus = $("#status").val();
    let objStatus = JSON.parse(jsonStatus);

    $("#overlay").fadeOut();
    $("#initialBox").fadeOut();
    $("#statusBox").fadeIn();
    $("#information").fadeIn();

    $("#menu").css("background-image", "url('image/avatar/" + objStatus.info.icon + "')");
    $("#menu").fadeIn();

    const se = new Audio('mp3/openGame.mp3');
    se.play();
    while(true){
        if(!objStatus.isGameSet){
            await checkStatus();
        }else{
            break;
        }
    };
}

//状況を確認・更新
async function checkStatus(){
    return await new Promise(async function(resolve, reject){
        try{
            let jsonStatus = $("#status").val();
            let objStatus = JSON.parse(jsonStatus);

            //$("#informationBody").html("最新の状態を確認中・・");
            const obj = await callAPI("getRoomStatus", $("#status").val());

            //背景変更
            let crntTimeZone = Number(obj.room.time_zone);
            let prevTimeZone = (crntTimeZone + 3) % 4;

            $(".backGroundDesign[data-index!=" + crntTimeZone + "]").fadeOut();
            $(".backGroundDesign[data-index=" + crntTimeZone + "]").fadeIn();

            //プレヤーの状況変更
            let html = "";
            let players = [];
            let cntKilled = 0;
            let cntLive = 0;
            let cntAdvance = 0;
            Object.values(obj.players).forEach(function (player) {
                let backGroundImage = "url(\"image/avatar/" + player.icon + "\")";
                let color = "orange";
                let grayScale = 0;

                //タイムゾーンが進んでいる人
                if(Number(player.time_zone) > crntTimeZone ||
                   (crntTimeZone==3 && Number(player.time_zone) == 0) ){
                    color = "green";
                    cntAdvance++;
                }
                //死んでる人
                if(player.killed == ""){
                    cntLive++;
                }else{
                    color = "black";
                    grayScale = 100;
                    cntKilled++;
                }

                //グラフィカルステータスのhtmlを形成
                let classOption = "select_" + ((player.name == objStatus.select) ? "on" : "off");

                if(player.name == objStatus.player ||
                   player.killed != ""){
                    classOption = "select_off select_disabled";
                }

                html += "<div class='select " + classOption + "' data-select='" + player.name + "' style='float:left;'>";
                html += "<div class='iconPlayer' style='background-color:" + color + "; background-repeat: no-repeat; background-image:" + backGroundImage + "; filter:grayscale(" + grayScale + "%);'>";
                html += (player.icon == "" ? "<br>未入室" : "");
                html += "</div>";
                html += "<div class='playerName'>" + player.name + "</div>"
                html += "</div>";
            });
            html += "<br style='clear:left;' />";
            $("#statusGraphic").html(html);

            html = "<div style='font-size:smaller;'>現在の状況(" + obj.serverTime + "時点)</div>";
            html += "<div style='font-size:smaller;'>"+ cntAdvance + "/" + cntLive + "人のプレイヤーが" + actionList[crntTimeZone] + "済です。</div>";
            html += "<div style='font-size:smaller;'>※" + cntKilled + "人のプレイヤーが退場しています。</div>";
            $("#statusText").html(html);

            //勝者が決している場合
            if(objStatus.isGameSet){
                return resolve();
            }else{
                await requestAction(objStatus, obj);

                setTimeout(function(){
                    return resolve();
                }, reloadInterval);
            }
        }catch(error){
            reject(error);
        }
    });
}

//プレイヤーへアクションを促す
function requestAction(objStatus, obj){
    return new Promise(async function(resolve, reject){
        try{
            //タイムゾーンを比較
            let timeZoneMine = Number(obj.players[objStatus.player].time_zone);
            let timeZoneRoom = Number(obj.room.time_zone);
            $("#timeZone").html((Number(obj.room.date) + 1) + "日目の" + timeZoneList[timeZoneRoom] + "です");

            let html = "";
            switch(timeZoneRoom){
                //夕刻（投票をする）
                case 0:
                    if(objStatus.info.killed != ""){
                        html += "<div style='margin-top:10px;'>あなたは敗退しました。草葉の陰から見守りましょう。</div>";
                    }else{
                        html += "<div style='width:50px; height:50px; border-radius: 50%; background-image: url(\"image/vote.png\"); background-size: cover; background-repeat: no-repeat;'></div>";
                         if(timeZoneMine == timeZoneRoom){
                            reloadInterval = 10000;
                            html += "人狼だと思う人を選んで投票してください。<br>";
                            html += "<div style='width:100%; text-align:center;'><div data-action='action_vote' class='action btn-square-pop'>投票する</div></div>";
                            html += "<br style='clear:left;' />";
                        }else{
                            html += "あなたは投票を済ませましたが、他のプレイヤーがまだです。<br>しばらくお待ち下さい。";
                        }
                    }
                    $("#informationBody").html(html);
                    break;

                //夜（投票結果確認）
                case 1:
                    await showResultVote(obj);

                    if(objStatus.info.killed != ""){
                        html += "<div style='margin-top:10px;'>あなたは敗退しました。草葉の陰から見守りましょう。</div>";
                        //html += "<div style='width:100%; text-align:center;'><div data-action='action_go_myroom' class='action btn-square-pop'>了解</div></div>";
                    }else if(timeZoneMine == timeZoneRoom){
                        html += "<div style='margin-top:10px;'>就寝時刻です。<br>結果を確認したら、自室へ戻りましょう。</div>";
                        html += "<div style='width:100%; text-align:center;'><div data-action='action_go_myroom' class='action btn-square-pop'>自室へ戻る</div></div>";
                    }else{
                        html = "あなたは自室に戻りましたが、他のプレイヤーがまだです。しばらくお待ち下さい。";
                    }
                    $("#informationBody").html(html);
                    break;

                //深夜（それぞれの行動を受付ける）
                case 2:
                    if(objStatus.info.killed != ""){
                        html += "<div style='margin-top:10px;'>あなたは敗退しました。草葉の陰から見守りましょう。</div>";
                    }else if(timeZoneMine == timeZoneRoom){
                        switch(objStatus.info.role){
                            case "murabito":
                                html = "あなたは村人です。<br>";
                                html = "人狼に襲われないことに期待しつつ、今日は眠りましょう。<br>";
                                html += "<div style='width:100%; text-align:center;'><div data-action='action_sleep' class='action btn-square-pop'>眠る</div></div>";
                                html += "<br style='clear:left;' />";
                                break;

                            case "rebaishi":
                                html = "あなたは霊媒師です。<br>";
                                html += "<div style='width:100%; text-align:center;'>";
                                html += "<div data-action='action_psychic' class='action btn-square-pop'>霊媒する</div>";
                                html += "<div data-action='action_sleep' class='action btn-square-pop'>眠る</div>";
                                html += "</div>";
                                html += "<br style='clear:left;' />";
                                break;

                            case "jinro":
                                html = "あなたは人狼です。襲撃対象を選択してください。<br>";
                                html += "<div id='UIPlayerSelect'></div>";
                                html += "<div style='width:100%; text-align:center;'><div data-action='action_attack' class='action btn-square-pop'>襲撃する</div></div>";
                                html += "<br style='clear:left;' />";
                                break;

                            case "yojinbo":
                                html = "あなたは用心棒です。守る対象を選択してください。<br>";
                                html += "<div id='UIPlayerSelect'></div>";
                                html += "<div style='width:100%; text-align:center;'><div data-action='action_defense' class='action btn-square-pop'>守る</div></div>";
                                html += "<br style='clear:left;' />";
                                break;
                        }
                    }else{
                        html = "あなたの行動は終了しましたが、他のプレイヤーがまだです。<br>しばらくお待ち下さい。";
                    }
                    $("#informationBody").html(html);
                    break;

                //朝（結果確認）
                case 3:
                    await showResultAttack(obj);
                    try{
                        let html = "";
                        const attackResult = JSON.parse(obj.room.killed);
                        if(attackResult.killed.length > 0){
                            attackResult.killed.forEach(function(killedPlayer){
                                html = "<div style='width:50px; height:50px; border-radius: 50%; background-image: url(\"image/kill.png\"); background-size: cover; background-repeat: no-repeat;'></div>";
                                if(killedPlayer == objStatus.info.name){
                                    html += "<div>あなたが襲撃されました。</div>";
                                    objStatus.info.killed = "killed";
                                }else{
                                    html += "<div>" + killedPlayer + "さんが襲撃されました。</div>";
                                }
                            });
                        }else{
                            html += "<div>昨夜の人狼の襲撃は失敗しました！</div>";
                        }

                        if(objStatus.info.killed != ""){
                            html += "<div style='margin-top:10px;'>あなたは敗退しました。草葉の陰から見守りましょう。</div>";
                            //html += "<div style='width:100%; text-align:center;'><div data-action='action_go_hall' class='action btn-square-pop'>了解</div></div>";
                        }else if(timeZoneMine == timeZoneRoom){
                            html += "<div style='margin-top:10px;'>結果を確認したら、誰が人狼か推理して次の投票に備えましょう。<br>準備が出来たら、投票のためにホールへ集まりましょう。</div>";
                            html += "<div style='width:100%; text-align:center;'><div data-action='action_go_hall' class='action btn-square-pop'>ホールへ行く</div></div>";
                        }else{
                            html = "あなたはホールへ付きましたが、他のプレイヤーがまだです。<br>しばらくお待ち下さい。";
                        }
                        $("#informationBody").html(html);
                    }catch(ex){
                        showError(ex + obj.room.killed);
                    }
                    break;
            }
            //プレイヤーの選択や、アクションをするためのロジック
            makeUIPlayerAction(obj, objStatus);

            //勝者が決している場合
            if(obj.room.winner != ""){
                html = obj.room.winner + "側の勝利です！";
                html += "<br style='clear:left;'>";
                objStatus.isGameSet = true;
                $("#status").val( JSON.stringify(objStatus) );

                Object.values(obj.players).forEach(function (player) {
                    if(player.winner == "1"){
                        html += "<div class='iconWinner'>"
                        html += "<div style='background-image:url(\"image/avatar/" + player.icon + "\");'>";
                        html += "</div>";
                        html += "<div style='text-align:center;font-size:smaller;color:white;'>" + player.Name + "さん</div>"
                        html += "</div>";
                    }
                });
                showMessage(html);
                $("#informationBody").html(obj.room.winner + "側が勝利しました！");
            }

            return resolve();
        }catch(error){
            reject(error);
        }
    });
}

//各種行動（vote:投票, goMyRoom:部屋へ戻る, sleep:眠る, psychic:霊媒, attack:襲撃, save:守る）
async function action(action){
    try{
        let jsonStatus = $("#status").val();
        let objStatus = JSON.parse(jsonStatus);
        if((action == "vote" ||
            action == "attack" ||
            action == "save") &&
           (objStatus.select == undefined ||
            objStatus.select == "")){
                await showMessage("対象を選択してください。");
                return;
        }

        const obj = await callAPI("action_" + action, $("#status").val());
        if(obj.msg != undefined){
            await showMessage(obj.msg);
            checkStatus();
        }
    } catch (error) {
        showError(error);
    }
}

//投票結果の表示
function showResultVote(obj){
    return new Promise(async function(resolve, reject){
        //確認済みかどうか？
        const VOTE_RESULT_CONFIRMED = $('meta[name="action_voteresult_confirmed"]').attr('content');
        let flgConfirmed = false;
        obj.ownHistorys.forEach(function(history){
            if(history.room_name == obj.room.name &&
               history.date == obj.room.date &&
               history.time_zone == obj.room.time_zone &&
               history.action == VOTE_RESULT_CONFIRMED){
                flgConfirmed = true;
            }
        });
        if(flgConfirmed){
            return resolve();
        }else{
            const jsonStatus = $("#status").val();
            const objStatus = JSON.parse(jsonStatus);
            let html = "";
            try{
                const votedResult = JSON.parse(obj.room.voted);
                if(votedResult.kill != "") {
                    const se = new Audio('mp3/punishment.mp3');
                    se.play();

                    html += "<div style='width:50px; height:50px; border-radius: 50%; background-image: url(\"image/punishment.png\"); background-size: cover; background-repeat: no-repeat;'></div>";
                    if (votedResult.kill == objStatus.info.name) {
                        html += "<div>投票の結果、あなたが退場となりました。</div>";
                        objStatus.info.killed = "killed";
                    } else {
                        html += "<div>投票の結果、" + votedResult.kill + "さんが退場となりました。</div>";
                    }
                }else{
                    html += "<div>" + votedResult.msg + "</div>";
                }
            }catch(ex){
                html += "<div>Parse Error:" + ex.message + "(" + obj.room.voted + ")</div>";
            }
            $("#dialogAlert").dialog({
                title: "投票結果",
                modal: true,
                position: {my: "center center" , at: "center center", of: window},
                show : "fade",
                hide : "fade",
                width: $("#main").width() * 0.9,
                height: $("#main").height() * 0.9,
                open : function(event, ui){
                    $("#dialogAlert").html(html);
                },
                buttons: [
                    {
                        text: "OK",
                        click: async function() {
                            $( this ).dialog( "close" );
                            await action(VOTE_RESULT_CONFIRMED);
                            return resolve();
                        }
                    }
                ]
            });
        }
    });
}

//襲撃結果の表示
function showResultAttack(obj){
    return new Promise(async function(resolve, reject){
        //確認済みかどうか？
        const ATTACK_RESULT_CONFIRMED = $('meta[name="action_attackresult_confirmed"]').attr('content');
        let flgConfirmed = false;
        obj.ownHistorys.forEach(function(history){
            if(history.room_name == obj.room.name &&
               history.date == obj.room.date &&
               history.time_zone == obj.room.time_zone &&
               history.action == ATTACK_RESULT_CONFIRMED){
                flgConfirmed = true;
            }
        });
        if(flgConfirmed){
            return resolve();
        }else{
            const jsonStatus = $("#status").val();
            const objStatus = JSON.parse(jsonStatus);
            let html = "";
            let attackResult = [];
            try{
                attackResult = JSON.parse(obj.room.killed);
                if(attackResult.killed.length > 0){
                    const se = new Audio('mp3/killed.mp3');
                    se.play();
                    attackResult.killed.forEach(function(killedPlayer){
                        html = "<div style='width:50px; height:50px; border-radius: 50%; background-image: url(\"image/kill.png\"); background-size: cover; background-repeat: no-repeat;'></div>";
                        if(killedPlayer == objStatus.info.name){
                            html += "<div>あなたが襲撃されました。</div>";
                            objStatus.info.killed = "killed";
                        }else{
                            html += "<div>" + killedPlayer + "さんが襲撃されました。</div>";
                        }
                    });
                }else{
                    const se = new Audio('mp3/saved.mp3');
                    se.play();
                    html += "<div>昨夜の人狼の襲撃は失敗しました！</div>";
                }

                //守られた人発表
                attackResult.saved.forEach(function(savedPlayer){
                    if(savedPlayer == objStatus.info.name){
                        html += "<div>あなたが守られました。</div>";
                    }else{
                        html += "<div>" + savedPlayer + "さんが守られました。</div>";
                    }
                });
                $("#dialogAlert").dialog({
                    title: "襲撃結果",
                    modal: true,
                    position: {my: "center center" , at: "center center", of: window},
                    show : "fade",
                    hide : "fade",
                    width: $("#main").width() * 0.9,
                    height: $("#main").height() * 0.9,
                    open : function(event, ui){
                        $("#dialogAlert").html(html);
                    },
                    buttons: [
                        {
                            text: "OK",
                            click: async function() {
                                $( this ).dialog( "close" );
                                await action(ATTACK_RESULT_CONFIRMED, $("#status").val());
                                return resolve();
                            }
                        }
                    ]
                });
            }catch(error){
                showError(ex + obj.room.killed);
            }
        }
    });
}


//選択対象のUI作成
function makeUIPlayerAction(obj, objStatus){

    //action class click event handler
    $(".action").click(async function(){
        const disabled = "disabled";
        if(!$(this).hasClass(disabled)){
            $(this).addClass(disabled);
            const actionName = $(this).data("action");
            const mode = $('meta[name="' + actionName + '"]').attr('content');;
            await action(mode);
            $(this).removeClass(disabled);
        }
    });

    //select class click event handler
    $(".select").click(function(){
        if(!$(this).hasClass("select_disabled")){
            $(".select").each(function(idx, elm){
                if(!$(elm).hasClass("select_disabled")){
                    $(elm).removeClass("select_on");
                    $(elm).addClass("select_off");
                }
            });
            $(this).removeClass("select_off");
            $(this).addClass("select_on");

            //選択状態を保持
            objStatus.select = $(this).data("select");
            $("#status").val( JSON.stringify(objStatus) );
        }
    });
}

//パスコード設定
function setPassCode(){
    return new Promise(async function(resolve, reject){
        let msgErr = "";
        let passCode = "";
        $(".tenKeyInput").each(function(){
            passCode = $(this).html();
        });

        if(passCode == ""){
            msgErr = "パスコードが空欄です";
        }else if(passCode.length > 10){
            msgErr = "パスコードは10桁以下にしてください。";
        }

        if(msgErr == ""){
            let jsonStatus = $("#status").val();
            let objStatus = JSON.parse(jsonStatus);
            objStatus.pass = passCode;
            $("#status").val( JSON.stringify(objStatus) );

            const obj = await callAPI("setPassCode", $("#status").val());

            objStatus.pass = obj.Pass;
            await showRole(false);

            $("#setPassCode").dialog("close");
            $("#status").val( JSON.stringify(objStatus) );
            return resolve(obj);
        }else{
            alert(msgErr);
        }
    });
}

//正体を表示する
async function showRole(isOpenning){
    let jsonStatus = $("#status").val();
    let objStatus = JSON.parse(jsonStatus);
    const obj = await callAPI("getPlayerInfo", $("#status").val());
    objStatus.info = obj.info;
    $("#status").val( JSON.stringify(objStatus) );
    let flg = true;

    if(!isOpenning)
        flg = await auth();

    if(flg){
        return new Promise(resolve =>{
            const se = new Audio('mp3/openDialog.mp3');

            $("#messageClose").hide();
            $("#message").data("isOpen", true);
            $("#message").slideDown();

            if(isOpenning){
                se.play();
                $("#messageBody").html("<div>ようこそ恐怖の人狼ゲームへ。</div>");
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>このゲームルームへのエントリーは、あなたを含めて全部で" + objStatus.info.playerTotal + "人</div>");
                },2000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>まだ" + (objStatus.info.playerTotal - objStatus.info.playerEntry) + "人が準備中のようです。</div>");
                },4000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>さて、まずはあなたの役割を確認しましょう。</div>");
                },6000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>あなたは・・・</div>");
                },8000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div><img src='image/" + objStatus.info.role + ".png' /></div>");
                    if(objStatus.info.jinrolist != undefined){
                        let friend = "";
                        objStatus.info.jinrolist.forEach(function(player){
                            if(player.Avater != ""){
                                let backGroundImage = "url(\"image/avatar/" + player.icon + "\")";
                                let color = "orange";

                                friend += "<div class='select_off select_disabled' style='float:left;'>";
                                friend += "<div class='iconPlayer' style='background-color:" + color + "; background-repeat: no-repeat; background-image:" + backGroundImage + ";'>";
                                friend += "</div>";
                            }
                            friend += "<div class='playerName' style='color:white;'>" + player.name + "</div>"
                            friend += "</div>";
                        });
                        if(friend != "")
                            $("#messageBody").append("<div>あなたの仲間は</div>" + friend + "<br style='clear:left;'>");
                    }
                },10000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>このことは、決して人には言わないように。</div>");
                },12000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>では、ゲームを始めましょう。</div>");
                },14000);
                setTimeout(function(){
                    se.play();
                    $("#messageBody").append("<div>OKボタンを押してください。</div>");
                    $("#messageClose").fadeIn();
                },16000);
            }else{
                $("#messageBody").html("<div>あなたは・・・</div>");
                setTimeout(function(){
                    $("#messageBody").append("<div><img src='image/" + objStatus.info.role + ".png' /></div>");
                    if(objStatus.info.jinrolist != undefined){
                        let friend = "";
                        objStatus.info.jinrolist.forEach(function(player){
                            if(player.Avater != ""){
                                let backGroundImage = "url(\"image/avatar/" + player.icon + "\")";
                                let color = "orange";

                                friend += "<div class='select_off select_disabled' style='float:left;'>";
                                friend += "<div class='iconPlayer' style='background-color:" + color + "; background-repeat: no-repeat; background-image:" + backGroundImage + ";'>";
                                friend += "</div>";
                            }
                            friend += "<div class='playerName' style='color:white;'>" + player.name + "</div>"
                            friend += "</div>";
                        });
                        if(friend != "")
                            $("#messageBody").append("<div>あなたの仲間は</div>" + friend + "<br style='clear:left;'>");
                    }
                },2000);
                setTimeout(function(){
                    $("#messageBody").append("<div>このことは、決して人には言わないように。</div>");
                },4000);
                setTimeout(function(){
                    $("#messageBody").append("<div>OKボタンを押してください。</div>");
                    $("#messageClose").fadeIn();
                },6000);
            }
            const timer = setInterval(function(){
                const isOpen = $("#message").data("isOpen");
                if(!isOpen){
                    clearInterval(timer);
                    return resolve();
                }
            },500);
        });
    }
}

function showMessage(msg){
    return new Promise(async function(resolve, reject){
        $("#message").data("isOpen", true);
        $("#messageBody").html(msg);
        $("#message").slideDown();
        const timer = setInterval(function(){
            const isOpen = $("#message").data("isOpen");
            if(!isOpen){
                clearInterval(timer);
                return resolve();
            }
        },500);
    });
}

//認証する
function auth(){
    return new Promise(async function(resolve, reject){
        $("#auth .msg").html("");
        $(".tenKeyInput").html("");

        $(".btn-ten-key").click(function(){
            const se = new Audio('mp3/pushTenkey.mp3');
            se.play();
            const num = $(this).html();
            $("#auth .tenKeyInput").each(function(){
                if(isNaN(num)){
                    $("#auth .tenKeyInput").html("");
                }else if($("#auth .tenKeyInput").html().length < 8){
                    let val = $("#auth .tenKeyInput").html();
                    $("#auth .tenKeyInput").html(val);
                }
            });
        });

        $("#auth .btnOK").click(function(){
            let jsonStatus = $("#status").val();
            let objStatus = JSON.parse(jsonStatus);
            let msgErr = "";
            let passCode = "";
            $(".tenKeyInput").each(function(){
                passCode = $(this).html();
            });

            if(passCode != objStatus.info.pass){
                msgErr = "パスコードが違います";
            }

            if(msgErr == ""){
                $("#auth").slideUp();
                return resolve(true);
            }else{
                const se = new Audio('mp3/pushTenkey.mp3');
                se.play();
                $("#auth .tenKeyInput").html(msgErr);
            }
        });

        $("#auth .btnCancel").click(function(){
            $("#auth").slideUp();
            return resolve(false);
        });

        const se = new Audio('mp3/openDialog.mp3');
        se.play();
        $("#auth").slideDown();
    });
}

//エンドポイントと通信する
function callAPI(mode, json){
    return new Promise(async function(resolve, reject){
        const endpoint = $('meta[name="api_path"]').attr('content') + mode;
        const requestParameter = JSON.parse(json);
        $.ajax({
                type: "POST",
                url: endpoint,
                data: {"param": requestParameter}
            })
            .done(function(res, status){
                try{
                    return resolve(res);
                }catch(ex){
                    $("#debug").val("error\n" + status);
                    return reject(ex + res);
                }
            })
            .fail(function(xhr) {
                if(xhr.responseJSON != undefined)
                    return reject("通信が失敗しました" + JSON.stringify(xhr.responseJSON));
                else
                    return reject("通信が失敗しました" + xhr);
            })
            .always(function(xhr, msg) {
            });
    });
}

function getParam(name) {
    const url = new URL(window.location.href);
    // URLSearchParamsオブジェクトを取得
    const params = url.searchParams;

    let ret = ''
    if(params.has(name)) {
      ret = params.get(name);
    }
    return ret;
}

function makeRoom(){
    let width = Math.min(600, $("#main").width());
    let height = Math.min(600, $("#main").height());
    $("#dialogMakeRoom").dialog({
        title: "部屋の作成",
        modal: true,
        position: {my: "center center" , at: "center center", of: window},
        show : "fade",
        hide : "fade",
        width: width,
        height: height,
        open : function(event, ui){
            $(".ui-dialog-titlebar-close", $(this).parent()).hide();
            makeRoomStep(1);
        },
        buttons: [
            {
                text: "キャンセル",
                click: function() {
                    $( this ).dialog( "close" );
                    choiceRoom(null);
                }
            }
        ]
    });
}

async function makeRoomStep(step){
    let msgErr = "";
    let json = $("#dialogMakeRoomParam").val();
    let param = JSON.parse(json);
    let roles = param.roles;

    $("#dialogMakeRoomErr").html("");

    switch(step){
        //役割設定
        case 1:
            $("#dialogMakeRoomBody").html($("#makeRoomStep1").html());
            $("#dialogMakeRoomBody input[data-type='roomName']").each(function(){
                $(this).val(param.roomName);
            });
            $("#dialogMakeRoomBody *[data-type='input']").each(function(){
                let type = $(this).prop("name");
                $(this).val(roles[type]);
            });
            break;

        //役割チェック→プレイヤー名入力
        case 2:
            $("#dialogMakeRoomBody *[data-type='input']").each(function(){
                let type = $(this).prop("name");
                let val = Number($(this).val());
                roles[type] = val;
            });
            param.cnt = roles.murabito + roles.jinro + roles.yojinbo + roles.rebaishi;
            if((roles.murabito + roles.yojinbo + roles.rebaishi) < roles.jinro * 3){
                msgErr = "村人(用心棒と霊媒師含む)は、人狼の3倍以上に設定する必要があります";
            }
            else if(roles.murabito < (roles.yojinbo + roles.rebaishi)){
                msgErr = "用心棒と霊媒師の総数は、村人と同数以下に設定する必要があります";
            }

            $("#dialogMakeRoomBody input[data-type='roomName']").each(function(){
                let roomName = $(this).val();
                roomName = roomName.split(" ").join("");
                roomName = roomName.split("　").join("");
                param.roomName = roomName;
                if(roomName == ""){
                    msgErr = "部屋名を入力してください。";
                }
            });

            //入力状態を保存
            $("#dialogMakeRoomParam").val(JSON.stringify(param));

            //部屋が存在するかどうか確認
            var obj = await callAPI("getRooms", $("#dialogMakeRoomParam").val());
            obj.rooms.forEach(function(room){
                if(room.name == param.roomName)
                    msgErr = "部屋「" + param.roomName + "」は既に存在します。別の部屋名を指定してください。";
            });

            if(msgErr != ""){
                $("#dialogMakeRoomErr").html(msgErr);
            }else{
                let html = "プレイヤーの名前を入れてください。<br>※スペース（前半角とも）は除去されます<br><br style='clear:left;' />";
                for(let i=1; i<=param.cnt; i++){
                    let elm = $( $("#makeRoomStep2").html() );
                    $(elm).find("input").each(function(){
                        $(this).prop("name", "player" + i);
                    });
                    $(elm).find("span.playerIndex").each(function(){
                        $(this).html(i);
                    });
                    html += elm.html();
                }
                html += "<div style='width:100%; text-aling:center; margin-top:5vh;'>";
                html += "<div style='width:20%; margin: 0 auto;'>";
                html += "<a href='javascript:makeRoomStep(3);' class='btn-square-pop' style='font-size:larger;'>次へ</a>";
                html += "</div>";
                html += "</div>";
                $("#dialogMakeRoomBody").html(html);
            }
            break;

        //プレイヤー名チェック→設定確認
        case 3:
            let players = [];
            $("#dialogMakeRoomBody input[data-type='playerName']").each(function(){
                let name = $(this).val();
                name = name.split(" ").join("");
                name = name.split("　").join("");
                players.push(name);
                if(name == ""){
                    $(this).focus();
                    msgErr = "プレイヤー名は全て設定してください。";
                }
            });
            if(msgErr != ""){
                alert(msgErr);
            }else{
                param.players = players;
                $("#dialogMakeRoomParam").val(JSON.stringify(param));
                let html = "以下の設定で部屋を作成しますか？";
                html += "<div>村人：" + roles.murabito + "人</div>";
                html += "<div>人狼：" + roles.jinro + "人</div>";
                html += "<div>用心棒：" + roles.yojinbo + "人</div>";
                html += "<div>霊媒師：" + roles.rebaishi + "人</div>";
                html += "<div>プレイヤー：" + param.players.join("さん、") + "さん</div>";
                html += "<br><a href='javascript:makeRoomStep(4);' class='btn-square-pop'>部屋を作成</a>";
                $("#dialogMakeRoomBody").html(html);
            }
            break;

        //プレイルーム作成
        case 4:
            var obj = await callAPI("makeRoom", $("#dialogMakeRoomParam").val());

            if(obj.error != undefined){
                $("#alert").html(obj.error);
                $("#alert").dialog({
                    position: {my: "center center" , at: "center center", of: window},
                    show : "fade",
                    hide : "fade",
                    open : function(event, ui){
                        $('.ui-dialog-titlebar').removeClass('ui-widget-header');
                    }
                });
            }else if(obj.msg != undefined){
                $("#dialogMakeRoom").dialog("close");
                showMessage(obj.msg);
            }else{
                $("#alert").html("おや？");
                $("#alert").dialog({
                    position: {my: "center center" , at: "center center", of: window},
                    show : "fade",
                    hide : "fade",
                    open : function(event, ui){
                        $('.ui-dialog-titlebar').removeClass('ui-widget-header');
                    }
                });
            }
            break;
    }
}

//部屋を選択する
async function choiceRoom(roomName){
    if(roomName === undefined){
        $("#dialogAlert").dialog({
            title: "選んでください",
            modal: true,
            position: {my: "center center" , at: "center center", of: window},
            show : "fade",
            hide : "fade",
            width: $("#main").width() * 0.9,
            height: $("#main").height() * 0.9,
            open : function(event, ui){
                $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                $(this).html("この部屋でプレイする以外の選択をしますか？");
            },
            buttons: [
                {
                    text: "部屋を作る",
                    click: function() {
                        makeRoom();
                        $(this).dialog("close");
                    }
                },
                {
                    text: "別の部屋にエントリーする",
                    click: function() {
                        choiceRoom("");
                        $(this).dialog("close");
                    }
                },
                {
                    text: "このアプリについて",
                    click: function(){
                        $(this).dialog("close");
                        dispDialogAbout();
                    }
                },
                {
                    text: "閉じる",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    }else if(roomName == null){
        $("#dialogAlert").dialog({
            title: "選んでください",
            modal: true,
            position: {my: "center center" , at: "center center", of: window},
            show : "fade",
            hide : "fade",
            width:$("#main").width() * 0.9,
            height:$("#main").height() * 0.9,
            open : function(event, ui){
                $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                $(this).html("部屋またはプレイヤーが存在しません。");
            },
            buttons: [
                {
                    text: "部屋を作る",
                    click: function() {
                        makeRoom();
                        $(this).dialog("close");
                    }
                },
                {
                    text: "部屋にエントリーする",
                    click: function() {
                        choiceRoom("");
                        $(this).dialog("close");
                    }
                },
                {
                    text: "このアプリについて",
                    click: function(){
                        $(this).dialog("close");
                        dispDialogAbout();
                    }
                },
            ]
        });
    }else if(roomName == ""){
        $("#dialogChoiceRoom").dialog({
            title: "選んでください",
            modal: true,
            position: {my: "center center" , at: "center center", of: window},
            show : "fade",
            hide : "fade",
            width:$("#main").width() * 0.9,
            height:$("#main").height() * 0.9,
            open : async function(event, ui){
                $(".ui-dialog-titlebar-close", $(this).parent()).hide();

                $("#choiceRoomBody").html("部屋情報を読込中・・");

                //存在する部屋を列挙
                const obj = await callAPI("getRooms", "{}");

                let html = "";
                if(obj.rooms != undefined){
                    let rooms = [];
                    obj.rooms.forEach(function (room) {
                        if(room.winner == ""){
                            rooms.push(room);
                        }
                    });
                    if(rooms.length > 0){
                        html += "<div>プレイする部屋を選んでください</div>";
                        rooms.forEach(function(room){
                            html += "<button class='btn-choice-room btn-square-pop' data-room='" + room.name + "'>" + room.name + "</button>";
                        });
                    }else{
                        html = "プレイ可能な部屋がありません。部屋を作ってください。";
                    }
                }else{
                    html = "Error";
                }

                $("#choiceRoomBody").html(html);
                $(".btn-choice-room").click(function(){
                    let room = $(this).data('room');
                    choiceRoom(room);
                });
            },
            buttons: [
                {
                    text: "このアプリについて",
                    click: function(){
                        $(this).dialog("close");
                        dispDialogAbout();
                    }
                },
                {
                    text: "閉じる",
                    click: function() {
                        $(this).dialog("close");
                        choiceRoom(null);
                    }
                }
            ]
        });
    }else{
        //部屋を指定
        $("#choiceRoomBody").html("プレイヤー情報を読込中・・");

        const obj = await callAPI("getPlayers", JSON.stringify({"room": roomName}));
        let html = "";
        if(obj.room != undefined &&
           obj.players != undefined){
            obj.players.forEach(function (player) {
                let url = "?room=" + obj.room + "&player=" + player.name;
                let qr = "https://chart.apis.google.com/chart?chs=100x100&cht=qr&chl=" + url;
                html += "<div style='text-align:center; float:left; width:150px; margin:15px;'>";
                //html += "<img src='" + qr + "' /><br>";
                html += "<a style='width:150px;' class='btn-square-pop' href='" + url + "'>" + player.name + "様</a>";
                html += "</div>";
            });
        }else{
            html = "Error";
        }

        $("#choiceRoomBody").html(html);
    }
}

function dispDialogAbout(){
    $("#dialogAbout").dialog({
        title: "このアプリについて",
        modal: true,
        position: {my: "center center" , at: "center center", of: window},
        show : "fade",
        hide : "fade",
        width:$("#main").width() * 0.8,
        height:$("#main").height() * 0.8,
        buttons: [
            {
                text: "閉じる",
                click: function() {
                    $(this).dialog("close");
                }
            }
        ]
    });

}

function showError(msg){
    showMessage("<h1>System Error</h1>" + msg);
}
