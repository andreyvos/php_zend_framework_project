<?php

abstract class T3Validator_Database extends T3Validator_Abstract{

  const defDbInitMethod = 'needsDatabaseInitialization';

  //const FIELD_NOT_EXIST = 'fieldNotExist';

  public $tableName;
  public $fieldName;

  public $database;


  public function getDatabase(){
    if($this->database === null)
      $this->database = T3Db::api();
    return $this->database;
  }

  public function defInit($tableName, $fieldName){
    $this->tableName = $tableName;
    $this->fieldName = $fieldName;
  }

  public static function needsDatabaseInitialization(){
    return true;
  }

 /* public function isValidCore($value){


    if(!array_key_exists($tableName, TableDescription::$table) || !array_key_exists($fieldName, TableDescription::$table[$tableName]))
      return $this->error(self::FIELD_NOT_EXIST);
      
    if($value === null || is_string($value) && empty($value))
      return true;

  }*/
}