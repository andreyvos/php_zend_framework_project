<?php

class T3Validator_NumericId extends T3Validator_Chain{

  public $uniqueValidator;

  public function  initialize() {

    $this->addValidator(new T3Validator_Int());

    $greater = new T3Validator_GreaterThan();
    $greater->defInit(0);
    $this->addValidator($greater);

    $this->uniqueValidator = new T3Validator_Unique();
    $this->addValidator($this->uniqueValidator);
  }

  public static function needsDatabaseInitialization(){
    return true;
  }

  public function defInit($tableName, $fieldName){
    $this->uniqueValidator->defInit($tableName, $fieldName);
  }


}