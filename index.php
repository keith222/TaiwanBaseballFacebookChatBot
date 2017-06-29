<?php
//header("Content-Type:text/html; charset=utf-8");
    
$access_token = "EAADXvVcaDQMBAA39ZCGK1W6EX2y0OSgUUDIZAEi6qvnThuabZCSNVrMhtD9ZARcjTSXUVO1aTMnz0iLPmZCHoLZAHzv7W48wIMjZBysGk4w2Jz76rZBoX6xe94LMffVd5229ZC750mDtOUogBNL4CIu2Uwr0IX09DMbZAHaHOJ0eirSAZDZD";
$verify_token = "taiwan-baseball-app-chat-bot";
$hub_verify_token = null;

if(isset($_REQUEST['hub_challenge'])) {
    $challenge = $_REQUEST['hub_challenge'];
    $hub_verify_token = $_REQUEST['hub_verify_token'];
}

if ($hub_verify_token === $verify_token) {
    echo $challenge;
}

$input = json_decode(file_get_contents('php://input'), true);

$sender = $input['entry'][0]['messaging'][0]['sender']['id'];
$message = $input['entry'][0]['messaging'][0]['message']['text'];
$message_to_reply = '';
// attached image
$message_image = '';

if(preg_match('[戰績|上半季|下半季]', strtolower($message))) {
    $season = 0;
    
    if(preg_match('[上半季]', strtolower($message))){
        $season = 1;
    }else if(preg_match('[下半季]', strtolower($message))){
        $season = 2;
    }

    $message_to_reply = get_rank_data($season);
    
}else if(preg_match('[象|獅|猿|悍|中信|兄弟|統一|lamigo|Lamigo|桃猿|富邦|悍將]', strtolower($message))){
    $team = '';
    
    if(preg_match('[象|中信|兄弟]', strtolower($message))){
        $team = '中信兄弟';
    }else if(preg_match('[獅|統一]', strtolower($message))){
        $team = '統一7-ELEVEn';
    }else if(preg_match('[猿|lamigo|Lamigo|桃猿]', strtolower($message))){
        $team = 'Lamigo';
    }else if(preg_match('[悍|富邦|悍將]', strtolower($message))){
        $team = '富邦';
    }

    $message_to_reply = get_team_rank_data($team);
    
}else if(preg_match('[hi|Hi|hello|嗨]', strtolower($message))){
    $message_to_reply = '嗨，我是 Taiwan Baseball App Facebook 聊天小精靈。你可以在這邊問我關於戰績、球員、賽事相關的情報唷。輸入 help 以取得資訊。';
}else if(preg_match('[選手-]', strtolower($message))){
    $param = explode('-',$message);
    $player = get_player_data($param[1]);
    $message_to_reply = $player[0];
    $message_image = $player[1];
    
}else if(preg_match('[help]', strtolower($message))){
    $message_to_reply = '可輸入問題： \\n';
    $message_to_reply .= '戰績：上半季、下半季、全年戰績、球隊名稱\\n';
    $message_to_reply .= '球員：選手-球員姓名 \\n';
    $message_to_reply .= '賽事：今天、明天、昨天、日期';
}else if(preg_match('[今天|昨天|明天]', strtolower($message))){
    $year = date("Y");
    $month = date("n");
    
    if(preg_match('[今天]', strtolower($message))){
        $day = date("d");
    }else if(preg_match('[昨天]', strtolower($message))){
        $day = date("d", strtotime("-1 days"));
    }else if(preg_match('[明天]', strtolower($message))){
        $day = date("d", strtotime("+1 days"));   
    }
    
    $date = $year."/".$month."/".$day;
    $message = get_game_info($date);
    
}else{
    $message_to_reply = '不好意思，暫時無法回答到你的問題。可以再多給我一點提示嗎？或是等等小編來回答你。';
}


//API Url
$url = 'https://graph.facebook.com/v2.9/me/messages?access_token='.$access_token;

$ch = curl_init($url);

// send image
if (strlen($message_image) > 0){
    $jsonData = '{
        "recipient":{
            "id":"'.$sender.'"
        },
        "message":{
            "attachment": {
                "type": "image",
                "payload": {
                    "url": "'.$message_image.'",
                    "is_reusable": true
                }
            }
        }
    }';
    
    $jsonDataEncoded = $jsonData;
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    if(!empty($input['entry'][0]['messaging'][0]['message'])){
        $result = curl_exec($ch);
    }
}


$jsonData = '{
    "recipient":{
        "id":"'.$sender.'"
    },
    "message":{
        "text":"'.$message_to_reply.'"
    }
}';



echo $jsonData;

$jsonDataEncoded = $jsonData;
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
if(!empty($input['entry'][0]['messaging'][0]['message'])){
    $result = curl_exec($ch);
}


// functions for reply

function get_rank_data($season){
    $year = date("Y");
    
    // get html data from cpbl
    $cpbl_url = 'http://www.cpbl.com.tw/standing/season/'.$year.'.html?season='.$season;

    $doc = new DOMDocument();
    $doc->loadHTMLFile($cpbl_url);

    // get rank table
    $rank_table = $doc->getElementsByTagName('table') -> item(0);
    $contents = $rank_table->getElementsByTagName('tr');

    $rank_message = '';
    foreach ($contents as $key => $value){
        if ($key == 0) continue;
        
        $detail = $value->getElementsByTagName('td');
        
        $rank_message .= '第'.$detail->item(0)->nodeValue.'名  '.preg_replace('/\s+/', '',$detail->item(1)->nodeValue).'\\n';
        $rank_message .= '勝-和-敗：'.preg_replace('/\s+/', '',$detail->item(3)->nodeValue).'\\n';
        $rank_message .= '勝率：'.preg_replace('/\s+/', '',$detail->item(4)->nodeValue).'\\n';
        $rank_message .= '勝場差：'.preg_replace('/\s+/', '',$detail->item(5)->nodeValue).'\\n';
        $rank_message .= '================\\n';
    }
        
    if (strlen($rank_message) == 0){
        $rank_message = "暫無資料";
    }
    
    $rank_message .= '\\n資料來源：\\n'.$cpbl_url;
    
    return $rank_message;
}

function get_team_rank_data($team){
    $year = date("Y");
    
    // get html data from cpbl
    $cpbl_url = 'http://www.cpbl.com.tw/standing/season/'.$year.'.html';

    $doc = new DOMDocument();
    $doc->loadHTMLFile($cpbl_url);

    // get rank table
    $rank_table = $doc->getElementsByTagName('table') -> item(0);
    $contents = $rank_table->getElementsByTagName('tr');

    $rank_message = '';
    foreach ($contents as $key => $value){
        if ($key == 0) continue;

        $team_data = $value->getElementsByTagName('td') -> item(1);
        $detail = $value->getElementsByTagName('td');
        
        if (preg_replace('/\s+/', '',$team_data->nodeValue) == $team){
            $rank_message .= '第'.$detail->item(0)->nodeValue.'名  '.preg_replace('/\s+/', '',$detail->item(1)->nodeValue).'\\n';
            $rank_message .= '勝-和-敗：'.preg_replace('/\s+/', '',$detail->item(3)->nodeValue).'\\n';
            $rank_message .= '勝率：'.preg_replace('/\s+/', '',$detail->item(4)->nodeValue).'\\n';
            $rank_message .= '勝場差：'.preg_replace('/\s+/', '',$detail->item(5)->nodeValue).'\\n';
            $rank_message .= '================\\n';
        }
        
    }
    
    if (strlen($rank_message) == 0){
        $rank_message = "暫無資料";
    }
    
    $rank_message .= '\\n資料來源：\\n'.$cpbl_url;
        
    return $rank_message;
}

function get_player_data($name){
    $data_message = '';
    
    // get html data from cpbl
    $cpbl_url = 'http://www.cpbl.com.tw/players.html?keyword='.$name;
    $doc = new DOMDocument();
    $doc->loadHTMLFile($cpbl_url);
    
    // get player detail url
    $player_table = $doc->getElementsByTagName('table')->item(0);
    $player_url = $player_table->getElementsByTagName('tr')->item(1)->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0)->getAttribute('href');
    
    if(player_url == '' || !$player_url) return '查無資料。';
    
    $player_url = "http://www.cpbl.com.tw".$player_url;
    
    // get player detail data
    $doc->loadHTMLFile($player_url);
    $finder = new DomXPath($doc);
    
    // get player's pic
    $classname = 'player_info_pic';
    $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
    $message_image = $nodes->item(0)->getElementsByTagName('img')->item(0)->getAttribute('src');

    // get player's info
    $classname = 'player_info_name';
    $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
    $info = $nodes->item(0)->nodeValue;
    $info = str_replace('球隊:',' ',$info);
    $info = preg_replace('/\s+/', ' ',$info);
    
    // get player's place
    $classname = 'player_info_other';
    $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
    $place = $nodes->item(0)->getElementsByTagName('tr')->item(0)->getElementsByTagName('td')->item(0)->nodeValue;
    $place = str_replace('位置:',' ',$place);
    $place = preg_replace('/\s+/', ' ',$place);
    
    // get player's record
    $classname = 'std_tb';
    $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
    $title = $nodes->item(0)->getElementsByTagName('tr')->item(0)->nodeValue;
    $title = preg_replace('/\s+/', ' | ',$title);
    
    $content = $nodes->item(0)->getElementsByTagName('tr');
    $year = date('Y');
    $player_data = '';
    foreach ($content as $key=>$value){
        $list_year = $value->getElementsByTagName('td')->item(0)->nodeValue;
        if ($key == 0) continue;
        if ($list_year == $year){
            $player_data = $value->nodeValue;
            $player_data = preg_replace('/\s+/', ' | ',$player_data);
        }
    }
    
    $data_message .= $info.' '.$place.'\\n';
    $data_message .= '================\\n';
    $data_message .= $title.'\\n';
    $data_message .= '================\\n';
    $data_message .= $player_data.'\\n';
    $data_message .= '================\\n';
    $data_message .= '\\n資料來源：\\n'.$player_url;
    
    return [$data_message,$message_image];
    
}

function get_game_inof($date){
    $firebase = "https://cpbl-fans.firebaseio.com/".$date.".json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$firebase);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);


    $data = curl_exec($ch);
    $data = (array)json_decode($data,true);
    curl_close($ch);
    
    foreach($temp as $value){
        $game_message = "日期：".$value["date"]."\n";
        $game_message .= "賽事編號：".$value["game"]."\n";
        $game_message .= "隊伍：".get_team_name($value["guest"])." VS ".get_team_name($value["home"])."\n";
        $game_message .= "分數：".$value["g_score"]." : ".$value["h_score"]."\n";
        $game_message = "場地：".$value["place"]."\n";
        $game_message = "=================\n";    
    }
    
    return $game_message;
}

function get_team_name($team){
    switch ($team){
        case 1:
            $name = "中信兄弟";
            break;
        case 2:
            $name = "統一7-ELEVEn獅";
            break;
        case 3:
            $name = "Lamigo桃猿";
            break;
        case 4:
            $name = "富邦悍將";
            break;
    }
    return $name;
}

?>