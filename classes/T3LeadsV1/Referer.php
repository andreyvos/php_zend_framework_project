<?php

class T3LeadsV1_Referer {
    static protected $data = array();
    
    static public function cache(array $ids){
        if(count($ids)){
            $ids = array_unique($ids);
            $data = T3Db::v1()->fetchPairs("select id,val from form_data where id in ('" . implode("','", $ids) . "') and var='first_referer' and val!=''"); 
            
            //T3_Print::varExport("cache Referefs");
            
            foreach($ids as &$id){
                
                if(isset($data[$id])){
                    self::$data[$id] = $data[$id];    
                }
                else {
                    self::$data[$id] = "";    
                }
            }
        }   
    }
    
    static public function renderRefererLink($leadID, $domain = '', $keyword = '', $linkText = false){
        if(!isset(self::$data[$leadID])) self::cache(array($leadID)); 
        $link = self::$data[$leadID];
        
        if(strlen($domain) && strlen($keyword)){
            return "<a href='{$link}' style='color:#069'><b>{$domain}</b></a>: <span style='color:#666'>{$keyword}</span>";    
        }
        else {
            
            
            if($link == "") return "-";
            if($linkText) return $link;
            return self::minLink($link);
        }
    }
    
    static public function minLink($link){
        $text = str_replace(array("http://", "https://"), array('',''), $link);
        
        if(strlen($text) > 50){
            $text = substr($text,0,45) . " <span style='color:#069'>...</span>";    
        }
        
        if(strlen($text) > 0){
            $endDomain = strpos($text, "/");
            $text = "<span style='color:#069'>" . substr($text, 0, $endDomain) . "</span>" . substr($text , $endDomain);    
        }
        
        
        return "<a style='cursor:pointer;color:#ACF;text-decoration:underline;' onclick=\"alert('{$link}')\">{$text}</a>";
    }     
}
