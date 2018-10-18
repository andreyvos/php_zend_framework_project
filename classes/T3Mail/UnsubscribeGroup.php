<?php

TableDescription::addTable('mail_unsubscribe_types', array(
   'id',
   'status',
   'manual',  
   'name', 
   'title',
   'description',
   'accessRoles',
   'defaultAction'
));  

class T3Mail_UnsubscribeGroup extends DbSerializable {
    public $id;
    
    public $status = 'activeShow';
    public $manual = '1';
    public $name;
    public $title;
    public $description;
    public $accessRoles = 'all';
    public $defaultAction = 'active';
    
    
    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('mail_unsubscribe_types'); 
    }  
    
    public function updateTemplates($array){
        if(!is_array($array))$array = array();
        $this->database->update('mail_templates_message', array('groupID' => '0'), 'groupID=' . $this->id);
        if(count($array))$this->database->update('mail_templates_message', array('groupID' => $this->id), 'groupID=0 and (id=' . implode(" or id=", $array) . ')'); 
    } 
    
    public function getUsers(){
        return $this->database->fetchAll("SELECT users.id,users.role,users.login,users.email,mail_unsubscribe_relations.update_date,mail_unsubscribe_relations.action
        FROM mail_unsubscribe_relations INNER JOIN users ON (mail_unsubscribe_relations.iduser = users.id) WHERE mail_unsubscribe_relations.idtype=? order by users.role, users.login", $this->id);
    }
    
    public function getTempaltes(){
        return $this->database->fetchAll("select id,`name`,`subject` from mail_templates_message where groupID=?", $this->id);
    } 
    
    
    
    public function isStatusActiveShow(){
        return ($this->status == "activeShow");
    }
    
    public function isStatusActiveHide(){
        return ($this->status == "activeHide");
    }
    
    public function isStatusNotActive(){
        return ($this->status == "notActive");
    } 
   
   
   
    
    public function isDefaultActionActive(){
        return ($this->defaultAction == "active");
    } 
    
    public function isDefaultActionUnsubscribe(){
        return ($this->defaultAction == "unsubscribe");
    }
    
    public function toArray($tables = null){
        if(is_array($this->accessRoles)){
            if(count($this->accessRoles)){
                $tempAccessRoles = $this->accessRoles;
                $this->accessRoles = implode(",", $this->accessRoles);
            }
            else {
                $tempAccessRoles = $this->accessRoles = '';     
            }
        }
        else {
            $tempAccessRoles = $this->accessRoles = (string)$this->accessRoles;    
        }
        
        $return = parent::toArray($tables);
        
        $this->accessRoles = $tempAccessRoles;
        
        return $return;
    }

    public function fromArray($array){
        parent::fromArray($array);
        
        if(is_string($this->accessRoles)){
            $this->accessRoles = explode(",", $this->accessRoles);
        }
        else if(!is_array($this->accessRoles)){
            $this->accessRoles = array();    
        }
    }     
}