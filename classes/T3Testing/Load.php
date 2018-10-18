<?php

class T3Testing_Load {
    static protected $isTest = false;
    
    public static $testBuyerId = '29114';
    public static $testBuyerChannelId = '10284';

    /**
    * Проверить находится ли система в тестовом режиме
    * @return bool
    */
    static public function isTest(){
        return self::$isTest;
    }

    
    /**
    * Установить текущий режим
    * true  - Test Mode
    * false - Standart Mode
    * 
    * @param bool $flag
    */
    static public function setTestMode($flag){
        self::$isTest = (bool)$flag;        
    }
    
    static protected function getTestServerPostChannels(){
        return array('103.28624');    
    }
    
    static public function isServerPostChannelTest($channelID){
        return in_array($channelID, self::getTestServerPostChannels());    
    }

}