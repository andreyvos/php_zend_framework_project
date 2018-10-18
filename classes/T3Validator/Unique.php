<?php

class T3Validator_Unique extends T3Validator_Database{

  const ERROR = 'error';


  public function isValidCore($value){

   $c = $this->getDatabase()->fetchOne("
      SELECT count(*) as c
      FROM $this->tableName
      WHERE $this->fieldName = ?
    ", array($value));

    if($c['c']!=0){
      $this->error(self::ERROR);
      return false;
    }

    return true;

  }


}
