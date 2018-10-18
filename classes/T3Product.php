<?php

TableDescription::addTable('leads_type', array(
    'id',
    'name',
    'title',
    'class_body',
    'activ',
    'offline_vefification',
    'buyer_reg_view',
    'seller_list_view',
    'groupid',
    'prioritet',
    'best',
    'new',
    'serverPost',
    
));


class T3Product extends DbSerializable{
    public $id;
    public $name;
    public $title;
    public $class_body; 
    public $activ; 
    public $offline_vefification; 
    public $buyer_reg_view; 
    public $seller_list_view; 
    public $groupid; 
    public $prioritet; 
    public $best; 
    public $new; 
    public $serverPost; 

    

    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('leads_type');
    }
    
    /**
    * @return T3LeadBody_Abstract
    */
    public function getBodyObject(){
        if($this->class_body){
            return new $this->class_body;
        }    
    }
    
}

