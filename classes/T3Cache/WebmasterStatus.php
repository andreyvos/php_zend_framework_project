<?php

class T3Cache_WebmasterStatus {
    
    static protected $a = array(
        'activ'     => array('Active',          '#D3F4B3'),
        'noappr'    => array('Not Approval',    '#FFFFFF'),
        'hold'      => array('Hold',            '#FBB7E3'),
        'lock'      => array('Lock',            '#FF9955'),
    	'temp'      => array('Temp',            '#FF9955'),
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
        return "<div class='tableStatus' style='background:" . self::$a[$status][1] . ";'><span style='color:#333;'>" . self::$a[$status][0] . "</span></div>";
    }     
}
