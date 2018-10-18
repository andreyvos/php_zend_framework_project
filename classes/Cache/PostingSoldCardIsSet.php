<?php

class Cache_PostingSoldCardIsSet extends AP_Cache_Abstract {
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
        Cache_PostingSoldCardInfo::instance()->load($ids);
    }
    
    public function get($id){
        $id = (int)$id;

        $all = Cache_PostingSoldCardInfo::instance()->get($id);

        $status = AP_UI_Decorator_Status::create();
        $status->addStatus_White('1', 'Set');
        $status->addStatus_Yellow('0', 'Noset');

        $status->setValue((string)(int)is_array($all));

        return "<a style='color:#000;' href='/en/account/posting/sold-card?id={$id}'>" . $status->render() . "</a>";
    }
}