<?php

class T3Validator_DriversLicenseNumber extends T3Validator_Chain {

  public function getMessage($code){
    switch($code){
      case T3Validator_Regex::NOT_MATCH   : return '%title% must contain only digits and letters';
      default                             : return parent::getMessage($code);
    }
  }

  public function  initialize() {

    $v = new T3Validator_StringLength();
    $v->defInit(4, 32);
    $this->addValidator($v);

    $v = new T3Validator_Regex();
    $v->defInit('/^([0-9A-Za-z]|\-|\.|\ ){4,32}$/');
    $this->addValidator($v);

  }
 
}