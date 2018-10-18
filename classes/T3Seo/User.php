<?php

TableDescription::addTable('seo_users', array(
    'id',
));

class T3Seo_User extends DbSerializable{
    public $id;

    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        
        $this->database = T3Db::seo();
        $this->readNewIdAfterInserting = false; 

        $this->tables = array('seo_users');
    }
    
    /**
    * @return T3User
    */
    public function getUserObject(){
        return T3Users::getUserById($this->id);    
    }
}

