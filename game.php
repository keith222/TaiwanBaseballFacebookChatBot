<?php
class Game{
    
    public $_date;
    
    public function __construct($date){
        $this->_date = $date;
    }
    
    public function get_game_info(){
        $firebase = "https://cpbl-fans.firebaseio.com/".$this->_date.".json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$firebase);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

        $data = curl_exec($ch);
        $data = (array)json_decode($data,true);
        curl_close($ch);

        if(empty($data)){
            return "無比賽資料。";
        }
        $game_message = "";
        foreach($data as $value){
            $game_message .= "日期：".$value["date"]."\\n";
            $game_message .= "賽事編號：".$value["game"]."\\n";
            $game_message .= "隊伍：".$this->get_team_name($value["guest"])." VS ".$this->get_team_name($value["home"])."\\n";
            $game_message .= "分數：".$value["g_score"]." ： ".$value["h_score"]."\\n";
            $game_message .= "場地：".$value["place"]."\\n";
            $game_message .= "賽事細節： http://www.cpbl.com.tw/games/box.html?&game_type=01&game_id=".$value["game"]."&game_date=".$value["date"]."&pbyear=".date("Y")."\\n";
            $game_message .= "=================\\n";    
        }

        return $game_message;
    }

    private function get_team_name($team){
        switch ($team){
            case "1":
                $name = "中信兄弟";
                break;
            case "2":
            case "2-1":
                $name = "統一7-ELEVEn獅";
                break;
            case "3":
                $name = "Lamigo桃猿";
                break;
            case "4":
                $name = "富邦悍將";
                break;
            case "4-1":
                $name = "義大犀牛";
                break;
            case "A-1":
                $name = "白";
                break;
            case "A-2":
                $name = "紅";
                break;
        }
        return $name;
    }
}
?>