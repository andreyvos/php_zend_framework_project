<?php

class T3Cache_PublisherSubaccount {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        $data = T3Db::apiReplicant()->fetchPairs("select id, name from `users_company_webmaster_subacc` where id in ('" . implode("','", $ids) . "')"); 

        foreach($ids as &$id){
            if(isset($data[$id]))   self::$data[$id] = substr($data[$id], 0, 32);    
            else                    self::$data[$id] = "Unknown: {$id}";        
        } 
    } 
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        return self::$data[$id];
    } 
}