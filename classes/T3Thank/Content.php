<?php

TableDescription::addTable('thank_contents', array(
   'id',
   'title',
   'class',
));        

class T3Thank_Content extends DbSerializable {
    public $id;
    public $title;
    public $class; 
    
    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('thank_contents'); 
    } 
    
    /**
    * @return T3Thank_Content_Abstract
    */
    public function getObject(){
        $class = $this->class;
        return new $class();
    }
}