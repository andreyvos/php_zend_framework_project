<?php

class T3LeadsV1_Status {
    
    static protected $a = array(
        'sold'          => array('Sold',            '#D3F4B3'),
        'pending'       => array('Pending',         '#f5f5f5'),
        'reject'        => array('Reject',          '#FFF'),
        'invalid'       => array('Error',           '#FF9955'),
        'duplicate'     => array('Dup',             '#FEEE92'),
        'noconnect'     => array('No Connect',      '#FF9955'),
        'nosend'        => array('Not Posted',      '#66CCFF'),
        'verification'  => array('Verification',    '#FBB7E3'),
        'filter'        => array('Filter',          '#F0FAFF'),
    );
    
    static public function renderStatus($status, $publisherMoney, $leadID){
        $currentUser =& T3Users::getInstance()->getCurrentUser();
        
        if($currentUser->isRoleWebmaster()){
            if($publisherMoney > 0)     $status = 'sold';
            else                        $status = 'reject';      
        }
        else {
            if($publisherMoney > 0)     $status = 'sold';     
        }
        
        return "<div class='leadStatus' style='background:" . self::$a[$status][1] . ";'>" . self::renderText($status, $leadID) . "</div>";
    }
    
    static protected function renderText($status, $leadID){
        $currentUser =& T3Users::getInstance()->getCurrentUser(); 
        
        if($currentUser->isRoleWebmaster()){
            return self::$a[$status][0];     
        }
        else {
            return "<a style='color:#333;' href='/en/account/lead/v1/response/id/{$leadID}'>" . self::$a[$status][0] . "</a>";    
        }
    }     
}
