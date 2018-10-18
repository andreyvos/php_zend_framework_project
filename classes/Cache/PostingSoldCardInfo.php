<?php

class Cache_PostingSoldCardInfo extends AP_Cache_Abstract {
    static protected $instance;
    /**
     * @return self
     */
    static public function instance(){
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    protected function select($ids){
        $all = array();
        $temp = T3Db::apiReplicant()->fetchAll(
            "SELECT * FROM `buyers_channels_sold_card` WHERE  `id` in ('" . implode("','", $ids) . "')"
        );
        if(count($temp)){
            foreach($temp as $el){
                $all[$el['id']] = $el;
            }
        }

        return $all;
    }

    public function get($id){
        $val = parent::get($id);

        if(is_array($val) && isset($val['logo'], $val['title']) && (strlen($val['logo']) || strlen($val['title']))){
            return $val;
        }

        return null;
    }
}