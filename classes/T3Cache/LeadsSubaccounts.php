<?php

class T3Cache_LeadsSubaccounts {
    static public $data;
    
    static public function load($ids){
        if(count($ids)){
            $ids = array_unique($ids);
            $data = T3Db::apiReplicant()->fetchPairs("select id,subacc_str from leads_data where id in ('" . implode("','", $ids) . "')"); 

            foreach($ids as &$id){
                if(isset($data[$id]))   self::$data[$id] = substr($data[$id], 0, 32);    
                else                    self::$data[$id] = $id;        
            }
        } 
    } 
    
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        
        return self::$data[$id];  
    } 
}
