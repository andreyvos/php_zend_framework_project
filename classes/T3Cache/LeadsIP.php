<?php

class T3Cache_LeadsIP {
    static public $data;
    
    static public function load($ids){
        if(count($ids)){
            $ids = array_unique($ids);
            $data = T3Db::apiReplicant()->fetchPairs("select id,ip_address from leads_data where id in ('" . implode("','", $ids) . "')"); 

            foreach($ids as &$id){
                if(isset($data[$id]))   self::$data[$id] = $data[$id];    
                else                    self::$data[$id] = $id;        
            }
        } 
    } 
    
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        return myHttp::get_ip_str(self::$data[$id]);  
    } 
}
