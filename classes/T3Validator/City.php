<?php

class T3Validator_City extends T3Validator_Chain{

  public function getMessage($code){
    return self::INVALID_TEXT;
  }

  public function initialize() {

    $v = new T3Validator_StringLength();
    $v->defInit(0, 128);
    $this->addValidator($v);

    $v = new T3Validator_Regex();
    $v->defInit('/^([a-zA-Z0-9]|\'|\-|\ |\&){1,128}$/');
    $this->addValidator($v);
    
  }

}