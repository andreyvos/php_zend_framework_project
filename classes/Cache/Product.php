<?php

class Cache_Product extends AP_Cache_Abstract {
    static protected $instance;
    /**
     * @return self
     */
    static public function instance(){
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    protected function select($ids){
        return T3Db::api()->fetchPairs("select `id`, title from leads_type where `id` in ('" . implode("','", $ids) . "')");
    }
}