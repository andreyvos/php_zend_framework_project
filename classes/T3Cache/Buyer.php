<?php

class T3Cache_Buyer {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        $data = T3Db::apiReplicant()->fetchPairs("select id, systemName from `users_company_buyer` where id in ('" . implode("','", $ids) . "')"); 

        foreach($ids as &$id){
            if(isset($data[$id]))   self::$data[$id] = $data[$id];  
            else if($id == 3000)    self::$data[$id] = "T3Leads";     
            else                    self::$data[$id] = "Unknown: {$id}";        
        } 
    } 
    
    static public function render($id, $link = true){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        if(!$link)              return self::$data[$id];
        else if($id == 3000)    return "<a style='text-decoration:none;'>" . self::$data[$id] . "</a>";
        else                    return "<a href='/en/account/buyers/main/id/{$id}' target='_blank'>" . self::$data[$id] . "</a>";
    } 
}