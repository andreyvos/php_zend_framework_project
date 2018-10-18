<?php

class T3CallCenter {
    const statusNewLead = 'new_leads'; 
    const statusReturned = 'returned'; 
    
    protected static $_instance = null;
    
    public $cacheAllLeads;
    
    /**
    * @return T3CallCenter
    */
    public static function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    static public function getAllCount($type)
    {
        if(!isset(self::getInstance()->cacheAllLeads[$type]))
        {
            self::getInstance()->cacheAllLeads[$type] = T3System::getConnect()->fetchOne("select count(*) from call_center_{$type} where `status`='wait' and active='1'");
        }
        return self::getInstance()->cacheAllLeads[$type]; 
    }
    
    static public function getMyItems($type, $status = 'wait'){
        return T3System::getConnect()->fetchAll("select * from call_center_{$type} where `status`=? and active='1' and userID=?", array($status, T3Users::getInstance()->getCurrentUser()->id)); 
    } 
    
    static public function getMyItemsCount($type, $status = 'wait'){
        return T3System::getConnect()->fetchOne("select count(*) from call_center_{$type} where `status`=? and active='1' and userID=?", array($status, T3Users::getInstance()->getCurrentUser()->id)); 
    } 
    
    static public function renderIFrame($width = '100%', $height = '100%'){
        return '<iframe id="CallCenterFlaPhoneIFrame" src="https://www.flaphone.com/login.php?login=' . urldecode(base64_encode(T3Users::getInstance()->getCurrentUser()->flaphone_login)) . 
            '&password=' . urldecode(base64_encode(T3Users::getInstance()->getCurrentUser()->flaphone_password)) . '&lang=ru" 
            frameborder="0" height="'.$height.'" width="'.$width.'" scrolling="0"></iframe>';
    }
    
    static public function getNewLeads($type){
        $stepCounts = 5;
        $maxCounts = 25;
        
        $result = array(
            'status' => 'ok',
            'reason' => '',
        );
        
        if(preg_match('/^[a-z0-9_]{1,99}$/i', $type)){
            $myCount = self::getMyItemsCount($type);
            
            if($myCount >= $maxCounts){
                $result['status'] = 'error';
                $result['reason'] = "Simultaneously max {$maxCounts} leads";    
            }
            else {
                T3System::getConnect()->query("update call_center_{$type} set userID=" . T3Users::getInstance()->getCurrentUser()->id . " where `status`='wait' and active='1' and userID='0' limit " . min($maxCounts - $myCount, $stepCounts));
                $result['status'] = 'ok';  
            }
        } 
        else {
            $result['status'] = 'error';
            $result['reason'] = 'Invalid Type';
        } 
        
        return $result;
    } 
    
    static public function freeMyLeads($type){
        T3System::getConnect()->query("update call_center_{$type} set userID=0 where `status`='wait' and userID=" . T3Users::getInstance()->getCurrentUser()->id );    
    }  
}