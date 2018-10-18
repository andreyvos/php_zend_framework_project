<?php

class T3Cache_VerificationStatus {
    
    static protected $a = array(
        'Phone'    => array('Phone', '#D3F4B3'),
        'Email'    => array('Email', '#FFFF99'),
        'Unknown'  => array('Unknown',            '#FEEE92'),  
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
    
    static public function render($status){
        MyZend_Site::addCSS('table/status.css');
        return "<div class='tableStatus' style='background:" . self::$a[$status][1] . ";'><span style='color:#333;'>" . self::$a[$status][0] . "</span></div>";
    }     
}
