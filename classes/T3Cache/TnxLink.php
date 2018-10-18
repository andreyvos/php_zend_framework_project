<?php

class T3Cache_TnxLink {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        $data = T3Db::apiReplicant()->fetchPairs("select id, url from tnx_links_base where id in ('" . implode("','", $ids) . "')"); 

        foreach($ids as &$id){
            if(isset($data[$id]))   self::$data[$id] = $data[$id];       
            else                    self::$data[$id] = "Unknown: {$id}";        
        } 
    } 
    
    static public function get($id, $link = true, $absolutePath = false){
        if($id == 0)    return "-";
        
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        $domain = "";
        if($absolutePath){
            $domain = "https://{$_SERVER['HTTP_HOST']}";    
        }
        
        if(!$link)              return self::$data[$id];
        else                    return T3Cache_Url::render(self::$data[$id]);
    } 
    
    static public function getNotLink($id){
        return self::get($id, false);    
    }
}