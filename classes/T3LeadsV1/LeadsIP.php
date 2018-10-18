<?php

class T3LeadsV1_LeadsIP {
    static public $data;
    
    static public function load($ids){
        if(count($ids)){
            $ids = array_unique($ids);
            $data = T3Db::v1()->fetchPairs("select idlead,clientIP from stat where idlead in ('" . implode("','", $ids) . "')"); 

            foreach($ids as &$id){
                if(isset($data[$id]))   self::$data[$id] = $data[$id];    
                else                    self::$data[$id] = $id;        
            } 
        }
    } 
    
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        return self::$data[$id];  
    } 
}