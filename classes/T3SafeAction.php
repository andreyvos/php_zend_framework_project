<?php

class T3SafeAction {
    static public function addSimpleAction($sessionName, $value){
        T3System::getConnect()->insert('safe_action',array(
            'createDatetime'    =>  new Zend_Db_Expr("NOW()"),
            'sessionName'       =>  $sessionName,
            'value'             =>  $value
        ));
    } 
    
    static function getCountAtSeconds($sessionName, $value, $seconds){
        return T3System::getConnect()->fetchOne("select count(*) from safe_action where sessionName=? and `value`=? and UNIX_TIMESTAMP(createDatetime) > UNIX_TIMESTAMP()-?", array($sessionName, $value, (int)$seconds));    
    }   
}