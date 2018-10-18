<?php

class T3TestCluster {
    static public function isTestMode(){
        if(isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], array(
            //'127.0.0.1',
            '68.169.64.26'
        ))){ 
            return true;
        }    
        return false;
    }    
}