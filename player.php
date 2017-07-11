<?php
class Player{
    
    public $player_name;
    
    public function __construct($name){
        $this->player_name = $name;
    }
    
    public function get_player_data(){
        $data_message = '';

        // get html data from cpbl
        $cpbl_url = 'http://www.cpbl.com.tw/players.html?keyword='.$this->player_name;
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
        $data_message .= '\\n測試資料：'.$cpbl_url;

        return [$data_message,$message_image];
    }
}
?>