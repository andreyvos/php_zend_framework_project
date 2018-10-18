<?php

TableDescription::addTable('tracking_settings', array(
    'id',
    'run',
    'checkResponce',
    'types',
    'url',
));    

class T3Tracking_Setting extends DbSerializable {

    public $id; // Webmaster ID
    public $run = 0;
    public $checkResponce = 1;
    public $types = array('newLead', 'leadAddPrice', 'leadReturn', 'newBonus', 'test');
    public $url = '';

    public function __construct() {
        parent::__construct();
        $this->tables = array('tracking_settings');
        $this->readNewIdAfterInserting = false;
    }
    
    public function isRun($type){
        if(
            $this->run && 
            is_array($this->types) && 
            count($this->types) && 
            in_array($type, $this->types)
        ){
            return true;
        }
        else {
            return false; 
        }   
    }
    
    
    public function load($webmasterID){
        $webmasterID = (int)$webmasterID;
        $this->fromDatabase($webmasterID);
        if($this->id == 0){
            $this->id = $webmasterID;
            $this->insertIntoDatabase();
        }    
    }
    
    /**
    * Предварительная обработка перед заисью в базу данных
    */
    public function toArray($tables = null){
        $temp = $this->types;
        
        $this->types = serialize($this->types);
        $return = parent::toArray($tables);
        
        $this->types = $temp;
        
        return $return;
    }

    /**
    * Обработка после получения из базы данных
    */
    public function fromArray(&$array){
        parent::fromArray($array);
        $this->types = unserialize($this->types);
    }

}