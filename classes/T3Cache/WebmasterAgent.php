<?php
/**
* Получить ID агента по ID вебматсра
*/

class T3Cache_WebmasterAgent {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        
        if(count($ids)){
            $data = T3Db::apiReplicant()->fetchPairs("select id, agentID from `users_company_webmaster` where id in ('" . implode("','", $ids) . "')"); 

            foreach($ids as $id){
                self::$data[$id] = isset($data[$id]) ? $data[$id] : 0;      
            } 
        }
    } 
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        return self::$data[$id]; 
    } 
}