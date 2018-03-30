<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
//header("Content-Type:text/html; charset=utf-8");
require_once("rank.php");
require_once("game.php");
require_once("player.php");

$index = new Index();
class Index{
    
    //tokens
    private static $access_token = "EAADXvVcaDQMBAEtXD9JAm5p99h1KRMOSILAYSNCYkivgCAxaaKk7Bc9wgzPtuWWUPISA6MSSMVZAaZAJfWsSLynMqiqkOkI3l6m68d1zJ9Ur18kdhgAXVLs69zojNPw0XZCiZBEWOGjPiv6wqZAXv43C0KVJ6W7xUOZB6guZAD1zgZDZD";
    private static $verify_token = "taiwan-baseball-app-chat-bot";
    
    private $sender;
    private $message;
    private $message_image;
    private $message_to_reply;
    private $input;
    private $payload;
    private $teamArray = ['中信兄弟','統一7-ELEVEn獅','Lamigo桃猿','富邦悍將'];
    private $isEnd = false;
    
    public function __construct(){
        $hub_verify_token = null;
        if(isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $hub_verify_token = $_REQUEST['hub_verify_token'];
        }
        if ($hub_verify_token === self::$verify_token) {
            echo $challenge;
        }
        $this->input = json_decode(file_get_contents('php://input'), true);
        
        $this->sender = $this->input['entry'][0]['messaging'][0]['sender']['id'];
        
        $messagingArray = $this->input['entry'][0]['messaging'][0];
        if(isset($messagingArray['postback'])){
            $this->payload = $messagingArray['postback']['payload'];
            
            if($this->payload == 'cpblbot'){
                $this->message = 'cpblbot';
                $this->handle_message();
                
            }else if($this->payload == 'rank'){
                $this->message = '請點選欲查閱的戰績排名表。';
                $this->send_rank_button_message($this->message);
                
            }else if($this->payload == 'team-rank'){
                $this->message = '請點選欲查閱的球隊戰績排名表。';
                $this->send_team_buttons();
                
            }else if($this->payload == 'player'){
                $this->message = '請輸入欲查詢的選手名稱。格式：選手-姓名。';
                $this->handle_message();
                
            }else if($this->payload == 'game'){
                $this->message = '請選擇欲查詢的比賽時間。';
                $this->send_game_buttons($this->message);
                
            }else if($this->payload == 'game-custom'){
                $this->message = '請輸入欲查詢日期。格式：年/月/日。';
                $this->handle_message();
            }
            
        }else if(isset($messagingArray['message'])){
            $this->message = $messagingArray['message']['text'];   
            if(isset($messagingArray['message']['quick_reply']['payload'])){
                $this->message = $messagingArray['message']['quick_reply']['payload'];
            }
            $this->handle_message();
        }
    }
    
    public function handle_message(){
        if(!empty($this->payload)){
            $this->send_message($this->message);
            $this->isEnd = ($this->payload == 'cpblbot');
            
            return;
        }
        
        if(preg_match('[all|tophalf|downhalf]', strtolower($this->message))) {
            // league rank
            $season = 0;
            if(preg_match('[tophalf]', strtolower($this->message))){
                $season = 1;
            }else if(preg_match('[downhalf]', strtolower($this->message))){
                $season = 2;
            }
            $rank = new Rank();
            $this->message_to_reply = $rank->get_rank_data($season);
            $this->isEnd = true;
            $rank = null;
            
        }else if(preg_match('[中信兄弟|統一7-ELEVEn獅|Lamigo桃猿|富邦悍將]', strtolower($this->message))){
            // team rank
            $team = '';
            if(preg_match('[中信兄弟]', strtolower($message))){
                $team = '中信兄弟';
            }else if(preg_match('[統一7-ELEVEn獅]', strtolower($message))){
                $team = '統一7-ELEVEn';
            }else if(preg_match('[Lamigo桃猿]', strtolower($message))){
                $team = 'Lamigo';
            }else if(preg_match('[富邦悍將]', strtolower($message))){
                $team = '富邦';
            }
            
            $rank = new Rank();
            $this->message_to_reply = $rank->get_team_rank_data($team);
            $this->isEnd = true;
            $rank = null;
            
        }else if(preg_match('[選手-]', strtolower($this->message))){
            $param = explode('-',$this->message);
            
            $player = new Player($param[1]);
            $player_info = $player->get_player_data($param[1]);
            $this->message_to_reply = $player_info[0];
            $this->message_image = $player_info[1];
            $this->isEnd = true;
            $player = null;
            
        }else if(preg_match('[today|yesterday|tomorrow]', strtolower($this->message))){
            $year = date("Y");
            $month = date("n");
            
            if(preg_match('[today]', strtolower($this->message))){
                $day = date("d");
            }else if(preg_match('[yesterday]', strtolower($this->message))){
                $day = date("d", strtotime("-1 days"));
            }else if(preg_match('[tomorrow]', strtolower($this->message))){
                $day = date("d", strtotime("+1 days"));   
            }
            $date = $year."/".$month."/".$day;
            
            $game = new Game($date);
            $this->message_to_reply = $game->get_game_info();
            $this->isEnd = true;
            $game = null;
            
        }else if (preg_match('/\d{4}\/\d{1,2}\/\d{1,2}/', strtolower($this->message), $result)){
            $game = new Game($result[0]);
            $this->message_to_reply = $game->get_game_info();
            $this->isEnd = true;
            $game = null;
        
        }else if(preg_match('[cpblbot]', strtolower($this->message))){
            $this->message_to_reply = '嗨，我是 Taiwan Baseball App Facebook 聊天小精靈。你可以在這邊問我關於戰績、球員、賽事相關的情報或是詢問關於 App 的問題唷。';
            $this->isEnd = true;
            
        }else{
            //$this->message_to_reply = '不好意思，暫時無法回答你的問題。可以再多給我一點提示嗎？或是輸入 help 查詢。或者等等小編來回答你。';
        }
        
        $this->send_message($this->message_to_reply);
    }
    private function send_message($message_to_reply){
        //API Url
        $url = 'https://graph.facebook.com/v2.11/me/messages?access_token='.self::$access_token;
        $ch = curl_init($url);
        //send image
        if (isset($this->message_image) && !empty($this->message_image)){
            $jsonData = '{
                "recipient":{
                    "id":"'.$this->sender.'"
                },
                "message":{
                    "attachment": {
                        "type": "image",
                        "payload": {
                            "url": "'.$this->message_image.'",
                            "is_reusable": true
                        }
                    }
                }
            }';
            
            $jsonDataEncoded = $jsonData;
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            if(!empty($this->input['entry'][0]['messaging'][0]['message'])){
                $result = curl_exec($ch);
            }
        }
        $jsonData = '{
            "recipient":{
                "id":"'.$this->sender.'"
            },
            "message":{
                "text":"'.$this->message_to_reply.'"
            }
        }';
        //echo $jsonData;
        $jsonDataEncoded = $jsonData;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if(!empty($this->input['entry'][0]['messaging'][0]['message']) || !empty($this->input['entry'][0]['messaging'][0]['postback'])){
            $result = curl_exec($ch);
        }
        
        if($this->input['entry'][0]['messaging'][0]['postback']['payload'] == 'cpblbot' || $this->isEnd == true){
            $this->send_menu_button_message("繼續操作?");
            $this->isEnd = false;
        }
    }
    
    private function send_menu_button_message($message){
        //API Url
        $url = 'https://graph.facebook.com/v2.11/me/messages?access_token='.self::$access_token;
        $ch = curl_init($url);
        
        $jsonData = '{
                "recipient":{
                    "id":"'.$this->sender.'"
                },
                "message":{
                    "attachment": {
                        "type":"template",
                        "payload":{
                            "template_type":"generic",
                            "elements":[
                                {
                                    "title":"'.$message.'",
                                    "image_url":"https://scontent.ftpe7-4.fna.fbcdn.net/v/t1.0-9/17352299_209141356235870_6608241796662669983_n.png?_nc_fx=ftpe7-4&_nc_cat=0&oh=854d7e3bc5ccfd75ffaf8c4b55b09e84&oe=5B29E989",
                                    "buttons":[
                                        {
                                            "type":"postback",
                                            "title":"比賽日程",
                                            "payload":"game"
                                        },
                                        {
                                            "type":"postback",
                                            "title":"戰績排名",
                                            "payload":"rank"
                                        },
                                        {
                                            "type": "postback",
                                            "title": "選手資料",
                                            "payload": "player"
                                        },
                                        {
                                            "type": "postback",
                                            "title": "與小編聊天",
                                            "payload": "other"
                                        }
                                    ]
                                }
                            ]
                        }
                    }
                }
            }';
            
        $jsonDataEncoded = $jsonData;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if(!empty($this->input['entry'][0]['messaging'][0]['message']) || !empty($this->input['entry'][0]['messaging'][0]['postback'])){
            $result = curl_exec($ch);
        }
    }
    
    private function send_game_button_message($message){
        //API Url
        $url = 'https://graph.facebook.com/v2.11/me/messages?access_token='.self::$access_token;
        $ch = curl_init($url);

        $jsonData = '{
                "recipient":{
                    "id":"'.$this->sender.'"
                },
                "message":{
                    "attachment": {
                        "type":"template",
                        "payload":{
                            "template_type":"generic",
                            "elements":[
                                {
                                    "title":"'.$message.'",
                                    "image_url":"https://scontent.ftpe7-4.fna.fbcdn.net/v/t1.0-9/17352299_209141356235870_6608241796662669983_n.png?_nc_fx=ftpe7-4&_nc_cat=0&oh=854d7e3bc5ccfd75ffaf8c4b55b09e84&oe=5B29E989",
                                    "buttons":[
                                        {
                                            "type":"postback",
                                            "title":"今日",
                                            "payload":"today"
                                        },
                                        {
                                            "type":"postback",
                                            "title":"明日",
                                            "payload":"tomorrow"
                                        },
                                        {
                                            "type": "postback",
                                            "title": "日期輸入",
                                            "payload": "game-custom"
                                        }
                                    ]
                                }
                            ]
                        }
                    }
                }
            }';

        $jsonDataEncoded = $jsonData;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if(!empty($this->input['entry'][0]['messaging'][0]['message']) || !empty($this->input['entry'][0]['messaging'][0]['postback'])){
            $result = curl_exec($ch);
        }
    }
    
    private function send_rank_button_message($message){
        //API Url
        $url = 'https://graph.facebook.com/v2.11/me/messages?access_token='.self::$access_token;
        $ch = curl_init($url);

        $jsonData = '{
                "recipient":{
                    "id":"'.$this->sender.'"
                },
                "message":{
                    "attachment": {
                        "type":"template",
                        "payload":{
                            "template_type":"generic",
                            "elements":[
                                {
                                    "title":"'.$message.'",
                                    "image_url":"https://scontent.ftpe7-4.fna.fbcdn.net/v/t1.0-9/17352299_209141356235870_6608241796662669983_n.png?_nc_fx=ftpe7-4&_nc_cat=0&oh=854d7e3bc5ccfd75ffaf8c4b55b09e84&oe=5B29E989",
                                    "buttons":[
                                        {
                                            "type":"postback",
                                            "title":"全年度",
                                            "payload":"all"
                                        },
                                        {
                                            "type":"postback",
                                            "title":"上半季",
                                            "payload":"tophalf"
                                        },
                                        {
                                            "type": "postback",
                                            "title": "下半季",
                                            "payload": "downhalf"
                                        }
                                    ]
                                }
                            ]
                        }
                    }
                }
            }';

        $jsonDataEncoded = $jsonData;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if(!empty($this->input['entry'][0]['messaging'][0]['message']) || !empty($this->input['entry'][0]['messaging'][0]['postback'])){
            $result = curl_exec($ch);
        }
    }
    
    private function send_team_buttons(){
        //API Url
        $url = 'https://graph.facebook.com/v2.11/me/messages?access_token='.self::$access_token;
        $ch = curl_init($url);
        
        $teamJson = '';
        for($i=0;$i<4;$i++){
            if($i==3){
                $teamJson .= '{"content_type":"text","title":"'.$this->teamArray[$i].'","payload":"'.$this->teamArray[$i].'"}';
            }else{
                $teamJson .= '{"content_type":"text","title":"'.$this->teamArray[$i].'","payload":"'.$this->teamArray[$i].'"},';    
            }
        }
        
        $jsonData = '{
                "recipient":{
                    "id":"'.$this->sender.'"
                },
                "message":{
                    "text": "請選擇隊伍名稱。",
                    "quick_replies":['.$teamJson.']
                }
            }';
            
        $jsonDataEncoded = $jsonData;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
    }
    

}
?>