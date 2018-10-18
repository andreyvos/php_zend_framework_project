<?php

class T3Cache_LeadsUserAgent {
    static public $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        $data = T3Db::apiReplicant()->fetchPairs("select lead_id,useragent from lead_useragent where lead_id in ('" . implode("','", $ids) . "')"); 

        foreach($ids as &$id){
            if(isset($data[$id]))   self::$data[$id] = $data[$id];    
            else                    self::$data[$id] = $id;        
        } 
    } 
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        return self::$data[$id];
    } 
}
