<?php

class T3Cache_LeadsReferer {
    static protected $data;
    
    static public function load($ids){
        if(is_array($ids) && count($ids)){
            $ids = array_unique($ids);
            
            $dataTemp = T3Db::apiReplicant()->fetchAll("select lead_id as id, keyword, domain, referrer from leads_visitors where lead_id in ('" . implode("','", $ids) . "')"); 
            $data = array();
            foreach($dataTemp as $el){
                $data[$el['id']] = $el;    
            }
            
            foreach($ids as &$id){
                if(isset($data[$id]))   self::$data[$id] = $data[$id]; 
                else                    self::$data[$id] = false;  
            } 
        }
    } 
    
    static public function render($id, $linkText = false){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        if($linkText){
            if(is_array(self::$data[$id]) && strlen(self::$data[$id]['referrer'])){   
                return self::$data[$id]['referrer'];    
            }
            else {
                return "-";    
            }    
        }

        if(is_array(self::$data[$id]) && strlen(self::$data[$id]['keyword']) && strlen(self::$data[$id]['domain'])){
            $key = urldecode(self::$data[$id]['keyword']);
            
            if(strlen(self::$data[$id]['referrer'])){   
                return "<a target='_blank' href='" . self::$data[$id]['referrer'] . "' style='color:#069'><b>" . self::$data[$id]['domain'] . "</b></a>: <span style='color:#666'>{$key}</span>"; 
            }
            else {
                return "<a style='color:#069'><b>" . self::$data[$id]['domain'] . "</b></a>: <span style='color:#666'>{$key}</span>";    
            }
        }
        
        if(is_array(self::$data[$id]) && strlen(self::$data[$id]['referrer'])){   
            return self::minLink(self::$data[$id]['referrer']);    
        }
        else {
            return "-";    
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
        
        
        return "<a style='cursor:pointer;color:#ACF;text-decoration:underline;' href='{$link}' target='_blank'>{$text}</a>";
    } 
}