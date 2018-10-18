<?php

class T3LeadsV1_Quality {
    
    static public function getTitle($name, $ifNot = 'unknown'){
        $a = array(
            'new'   => 'New',
            'dead'  => 'Old',
            'dup'   => 'Duplicate', 
        );
        
        return isset($a[$name]) ? $a[$name] :  $ifNot;
    }        
}