<?php

class T3CanadaExitpage {

    private $database;

    private static $_instance = null;

    public function __construct(){
        $this->database = T3Db::api();
    }

    public static function getInstance(){
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public static function getinfo($lead_id) {
        $sql = "SELECT * FROM canada_diana WHERE lead_id=$lead_id AND is_send=0";
        return self::getInstance()->database->fetchRow($sql);    
    }
    
    public static function issend($lead_id) {
        $sql = "SELECT COUNT(id) FROM canada_diana WHERE lead_id=$lead_id AND is_send=1";
        return self::getInstance()->database->fetchOne($sql);        
    }

    public static function send($id) {
        $lead = new T3Lead();
        if ($lead->fromDatabase($id)){
            $lead->getBodyFromDatabase();
            $postResult = $lead->postToBuyer(11449, false);
            if ($postResult->isSend()){
                return true;    
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}



