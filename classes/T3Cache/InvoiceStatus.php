<?php

class T3Cache_InvoiceStatus {
    
    static protected $a = array(
        'not_paid'     => array('Not Paid',  '#FAFAFA'),
        'fully_paid'    => array('Close', '#D3F4B3'),
        'was_overdue'     => array('Overdue',  '#FEF0A5'), 
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
    
    static public function renderStatus($status){
        MyZend_Site::addCSS('table/status.css');
        return "<div class='tableStatus' style='background:" . self::$a[$status][1] . ";'><span style='color:#333;'>" . self::$a[$status][0] . "</span></div>";
    }     
}
