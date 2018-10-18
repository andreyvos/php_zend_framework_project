<?php

TableDescription::addTable('seo_projects', array(
    'id',
    'status',
    'title',
));

class T3Seo_Project extends DbSerializable{
    public $id;
    public $status;
    public $title;

    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        
        $this->database = T3Db::seo();
        $this->readNewIdAfterInserting = true; 

        $this->tables = array('seo_projects');
    }
}

