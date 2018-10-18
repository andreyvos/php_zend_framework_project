<?php

class T3Cache_PostChannelStatus {
    
    static protected $a = array(
        'active'        => array('Active',          '#D3F4B3'),
        'verification'  => array('Verif',    '#FBB7E3'),
        'just_created'  => array('Created',    '#FFFF66'),
        'paused'        => array('Paused',          '#FFFFFF'),
        'deleted'       => array('Deleted',         '#FF9955'),
    );
    
    static public function getStatuses(){
        return array_keys(self::$a);    
    }
    
    static public function getStatusesAndTitle(){
        $data = array();
        
        foreach(self::$a as $key => $el){
            $data[$key] = $el[0];    
        }
        
        return $data;    
    }
    
    static public function renderStatus($status, $leadID){
        MyZend_Site::addCSS('table/status.css');
        return "<div class='tableStatus' style='background:" . self::$a[$status][1] . ";'><a style='color:#333;' href='/en/account/webmaster-channels/server-post-route/id/{$leadID}'>" . self::$a[$status][0] . "</a></div>";
    }     
}
