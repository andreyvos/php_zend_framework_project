<?php

class Cache_ProductName extends AP_Cache_Abstract {
    static protected $instance;
    /**
    * @return self
    */
    static public function instance(){
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }
    
    protected function select($ids){ 
        return T3Db::apiReplicant()->fetchPairs("select `name`, title from leads_type where `name` in ('" . implode("','", array_unique($ids)) . "')");
    }
}