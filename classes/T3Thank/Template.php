<?php

TableDescription::addTable('thank_templates', array(
   'id',
   'title',
   'class',
));        

class T3Thank_Template extends DbSerializable {
    public $id;
    public $template;
    public $class;    
    
    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('thank_templates'); 
    } 
    
    /**
    * @return T3Thank_Template_Abstract
    */
    public function getObject(){
        $class = $this->class;
        return new $class();
    }
}