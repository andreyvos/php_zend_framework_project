<?php

class T3Tracking_Settings {
    static protected $settings;
    
    /**
    * @param int $webmasterID
    * @return T3Tracking_Setting
    */
    static public function getSetting($webmasterID){
        $webmasterID = (int)$webmasterID;
        if(!isset(self::$settings[$webmasterID])){
            self::$settings[$webmasterID] = new T3Tracking_Setting();
            self::$settings[$webmasterID]->load($webmasterID);    
        }
        return self::$settings[$webmasterID];
    }  
    
    static public function updateParam($webmasterID, $param, $var){
        self::getSetting($webmasterID);
        self::$settings[$webmasterID]->$param = $var;   
    }
}