<?php

TableDescription::addTable('leads_body_changing_log', array(
  'id',
  'lead_id',
  'author_id',
  'changing_datetime',
  'field_name',
  'initial_value',
  'final_value',
));

class T3LeadBodyChangingLogItem extends DbSerializable{
 
  public $id;
  public $lead_id;
  public $author_id;
  public $changing_datetime;
  public $field_name;
  public $initial_value;
  public $final_value;

  public function __construct($setDatetimeNow = false) {

    if (!isset($this->className))$this->className = __CLASS__;

    parent::__construct();

    $this->tables = array('leads_body_changing_log');

    if($setDatetimeNow)
      $this->setDatetimeToNow();

  }

  public function setDatetimeToNow(){
    $this->changing_datetime = mySqlDateTimeFormat();
  }

  public function setCurrentUserAsAuthor(){
    $this->author_id = T3Users::getInstance()->getCurrentUserId();
  }

  public function record($setDatetimeNow = true, $setCurrentUserAsAuthor = true) {
    if($setDatetimeNow)
      $this->setDatetimeToNow();
    if($setCurrentUserAsAuthor)
      $this->setCurrentUserAsAuthor();      
    $this->insertIntoDatabase();
  }

}








