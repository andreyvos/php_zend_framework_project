<?php


class T3TimeZone {
    protected static $_instance = null;
    public $timeZones;
    public $defaultTimeZome;

    public function __construct(){
        $this->defaultTimeZome = 'pst';
        
        $this->timeZones = array(
            'pst' => 'PST - Pacific Standard Time (CA, T3Leads)', 
            'mst' => 'MST - Mountain Standard Time', 
            'cst' => 'CST - Central Standard Time', 
            'est' => 'EST - Eastern Standard Time (NY)', 
            'gmt' => 'GMT - Greenwich Mean Time. UK',
            'msk' => 'MSK - Moscow Standard Time. Russia',
        );            
    }
    
    /**
    * Возвращает объект класса T3TimeZone
    * @return T3TimeZone
    */
    static public function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self(); 
        }
        return self::$_instance;
    }
    
    /**
    * Массив тайм зон, используемых на t3Leads
    * @return array
    */
    static public function getTimeZones(){
        return self::getInstance()->timeZones;        
    }
    
    /**
    * Полуение таймзоны по умолчанию
    * @return string timezone abbr
    */
    static public function getDefaultTimeZone(){
        return self::getInstance()->defaultTimeZome;        
    }


}



