<?php

class T3Cache_AgentsPaymentsStatus {
    
    static protected $a = array(
        'new'              => array('New',            '#D3F4B3'),
        'history'          => array('History',        '#FFF'),
    );
    
    static public function renderStatus($status){
        MyZend_Site::addCSS('table/status.css'); 
        return "<div class='tableStatus' style='background:" . ifset(self::$a[$status][1], '#33F') . ";color:#333;text-align:center;'>" . ifset(self::$a[$status][0], $status) . "</div>";
    }   
}
