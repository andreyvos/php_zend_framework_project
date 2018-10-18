<?php

class Cache_Subaccount extends AP_Cache_Abstract {
    static protected $instance;
    /**
    * @return self
    */
    static public function instance(){
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }
    
    protected function select($ids){ 
        return;
    }
    
    public function load($ids){    
        T3Cache_PublisherSubaccount::load($ids);  
    }
    
    public function get($id){
        return T3Cache_PublisherSubaccount::get($id);
    }
}