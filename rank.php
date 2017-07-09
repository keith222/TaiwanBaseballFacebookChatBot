<?php
class Rank{
    
    private static $default_url = "http://www.cpbl.com.tw/standing/season/";
    private static $year = date("Y");
    
    public function __construct(){}
    
    public function get_rank_data($season){
        // get html data from cpbl
        $cpbl_url = $default_url.$this->$year.'.html?season='.$season;

        return $this->get_rank_from_url($cpbl_url,null);
    }

    public function get_team_rank_data($team){
        // get html data from cpbl
        $cpbl_url = $default_url.$this->$year.'.html';

        return $this->get_rank_from_url($cpbl_url,$team);
    }
    
    private function get_rank_from_url($url, $team){
        // get html data from url
        $doc = new DOMDocument();
        $doc->loadHTMLFile($url);

        // get rank table
        $rank_table = $doc->getElementsByTagName('table')->item(0);
        $contents = $rank_table->getElementsByTagName('tr');
        
        $rank_message = '';
        foreach ($contents as $key => $value){
            if ($key == 0) continue;

            $detail = $value->getElementsByTagName('td');
            
            // if want to get team rank
            if ($team != null){
                $team_data = $value->getElementsByTagName('td')->item(1);
                
                if (preg_replace('/\s+/', '',$team_data->nodeValue) != $team){
                    continue;
                }
            }
            
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
}

?>