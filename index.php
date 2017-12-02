<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
//header("Content-Type:text/html; charset=utf-8");
require_once("rank.php");
require_once("game.php");
require_once("player.php");

$index = new Index();
$index->handle_message();
class Index{
    
    //tokens
    private static $access_token = "EAADXvVcaDQMBAKiffb7yge54KQWKKH4IdZAW62MEBz90WJmzxofgIoqQBdZBa0VoaTm8vL5gsHXoZCtyNUfu3xH5JaZC3HghBOA7O7wxmgLDoSJCZCvYIsFtGN32zg4zouF1Ml4ZCVczOmcdUtXWBdMXGkHA6genYEfeKTSGZBZAhgZDZD";
    private static $verify_token = "taiwan-baseball-app-chat-bot";
    
    private $sender;
    private $message;
    private $message_image;
    private $message_to_reply;
    private $input;
    
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
            if($messagingArray['postback']['payload'] == 'hi'){
                $this->message = "hi";
            }
        }else if(isset($messagingArray['message'])){
            $this->message = $messagingArray['message']['text'];   
        }
    }
    
    public function handle_message(){
//        if(preg_match('[戰績|上半季|下半季]', strtolower($this->message))) {
//            // league rank
//            $season = 0;
//            if(preg_match('[上半季]', strtolower($this->message))){
//                $season = 1;
//            }else if(preg_match('[下半季]', strtolower($this->message))){
//                $season = 2;
//            }
//            $rank = new Rank();
//            $this->message_to_reply = $rank->get_rank_data($season);
//            $rank = null;
//        }else if(preg_match('[象|獅|猿|悍|中信|兄弟|統一|lamigo|富邦]', strtolower($this->message))){
//            // team rank
//            $team = '';
//            if(preg_match('[象|中信|兄弟]', strtolower($message))){
//                $team = '中信兄弟';
//            }else if(preg_match('[獅|統一]', strtolower($message))){
//                $team = '統一7-ELEVEn';
//            }else if(preg_match('[猿|lamigo]', strtolower($message))){
//                $team = 'Lamigo';
//            }else if(preg_match('[悍|富邦]', strtolower($message))){
//                $team = '富邦';
//            }
//            
//            $rank = new Rank();
//            $this->message_to_reply = $rank->get_team_rank_data($team);
//            $rank = null;
//            
//        }else if(preg_match('[選手-]', strtolower($this->message))){
//            $param = explode('-',$this->message);
//            
//            $player = new Player($param[1]);
//            $player_info = $player->get_player_data($param[1]);
//            $this->message_to_reply = $player_info[0];
//            $this->message_image = $player_info[1];
//            $player = null;
//        }else if(preg_match('[今|昨|明]', strtolower($this->message))){
//            $year = date("Y");
//            $month = date("n");
//            if(preg_match('[今]', strtolower($this->message))){
//                $day = date("d");
//            }else if(preg_match('[昨]', strtolower($this->message))){
//                $day = date("d", strtotime("-1 days"));
//            }else if(preg_match('[明]', strtolower($this->message))){
//                $day = date("d", strtotime("+1 days"));   
//            }
//            $date = $year."/".$month."/".$day;
//            
//            $game = new Game($date);
//            $this->message_to_reply = $game->get_game_info();
//            $game = null;
//        }else if (preg_match('/\d{4}\/\d{1,2}\/\d{1,2}/', strtolower($this->message), $result)){
//            $game = new Game($result[0]);
//            $this->message_to_reply = $game->get_game_info();
//            $game = null;
//        
//        }else if(preg_match('[help]', strtolower($this->message))){
//            $question = '可輸入的問題： \\n';
//            $question .= '戰績：上半季、下半季、全年戰績、球隊名稱\\n';
//            $question .= '球員：選手-球員姓名 \\n';
//            $question .= '賽事：今天、明天、昨天、年/月/日（ex. 2017/7/1）';
//            
//            $this->message_to_reply = $question;
//            
//        }else if(preg_match('[hi|hello|嗨]', strtolower($this->message))){
//            $this->message_to_reply = '嗨，我是 Taiwan Baseball App Facebook 聊天小精靈。你可以在這邊問我關於戰績、球員、賽事相關的情報唷。輸入 help 以取得資訊。或是留下訊息讓小編回答。';
//            
//        }else{
//            //$this->message_to_reply = '不好意思，暫時無法回答你的問題。可以再多給我一點提示嗎？或是輸入 help 查詢。或者等等小編來回答你。';
//        }
        $this->message_to_reply = $this->message;
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
    }
}
?>