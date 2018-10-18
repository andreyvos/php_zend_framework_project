<?php

class TableDescription_Table {

  public $name;
  public $fields = array();
  public $validators = array();
  public $class;
  public $idFieldName;
  public $saveExceptions = array();
  
  public function  __construct($name) {
    $this->name = $name;
  }

  public function addSaveException($fieldName){
    $this->saveExceptions[] = $fieldName;
  }

}
