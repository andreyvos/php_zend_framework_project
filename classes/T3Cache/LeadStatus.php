<?php

class T3Cache_LeadStatus {
    
    static protected $a = array(
        'sold'          => array('Sold',        '#D3F4B3', "Lead was sold"), // все
        'pending'       => array('Pending',     '#D8BFD8', "Lead is pending for decision"), // все
        'reject'        => array('Reject',      '#FFF',    "Leads is rejected"), // все
        'error'         => array('Error',       '#FF9955', "Lead was rejected due to containing incorrect data submitted by webmaster"), // все
        'duplicate'     => array('Duplicate',   '#FEEE92', "Lead was already presented to all Buyers"), // все
        //'timeout'       => array('Timeout',     '#FF9955', ""),
        //'noconect'      => array('nConn',       '#FF9955', ""),
        'nosend'        => array('nSend',       '#66CCFF', "Lead was not sent to any Buyer"), // админы
        'verification'  => array('Verif',       '#FBB7E3', "Lead is in the verification process"), // все
        'process'       => array('Process',     '#FFFF66', "Lead is in the automatic processing status"), // все
    );
    
    static public function renderStatus($status, $publisherMoney, $leadID){
        MyZend_Site::addCSS('table/status.css');
        
        $currentUser =& T3Users::getInstance()->getCurrentUser();
        
        if($currentUser->isRoleWebmaster()){
            
            if($publisherMoney > 0 && $status != 'process')             $status = 'sold';
            else  {
                if($status == 'error')                                  $status = 'error';  
                else if($status == 'process')                           $status = 'process'; 
                else if($status == 'verification')                      $status = 'verification';  
                else if($status == 'duplicate')                         $status = 'duplicate';  
                else if($status == 'nosend' || $status == 'pending')    $status = 'pending';
                else                                                    $status = 'reject'; 
            }     
        }
        else {
            if($publisherMoney > 0 && $status != 'process')     $status = 'sold'; 
            else if($publisherMoney !== null && $publisherMoney == 0 && $status == 'sold')  $status = 'reject';   
        }
        
        return "<div class='tableStatus' style='background:" . self::$a[$status][1] . ";'>" . self::renderText($status, $leadID) . "</div>";
    }
    
    static protected function renderText($status, $leadID){
        $currentUser =& T3Users::getInstance()->getCurrentUser(); 
        
        if($currentUser->isRoleWebmaster()){
            return self::$a[$status][0];     
        }
        else if($currentUser->isRoleWebmasterAgent()){
            return "<a style='color:#333;' href='/en/account/lead/log-get/id/{$leadID}'>" . self::$a[$status][0] . "</a>";
        }
        else {

            return "<a style='color:#333;' href='/en/account/lead/log/id/{$leadID}'>" . self::$a[$status][0] . "</a>";
        }
    }
    
    static public function renderDescription(){
        if(T3Users::getCUser()->isRoleWebmaster()){
            return self::getDescriptionText(array_keys(self::$a));    
        }
        else {
            return self::getDescriptionText(array('sold', 'error', 'process', 'verification', 'pending', 'reject')); 
        }
    } 
    
    static protected function getDescriptionText($arrayStatuses){
        $result = '';
        
        if(is_array($arrayStatuses) && count($arrayStatuses)){
            foreach($arrayStatuses as $key){
                if(isset(self::$a[$key]) && strlen(self::$a[$key][2])){
                    $result.= "<b>" . self::$a[$key][0] . "</b> - " . self::$a[$key][2] . "<br><br>";    
                }           
            }
        }
        
        if(strlen($result) > 10){
            $result = substr($result, 0, strlen($result) - 8);    
        }
        
        return $result;
    }    
}
