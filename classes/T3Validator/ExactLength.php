<?php

class T3Validator_ExactLength extends T3Validator_Abstract{

  protected $length = 0;

  const LENGTH_NOT_EQUAL = 'lengthNotEqual';

  protected function getMesVar($var){
    switch($var){
      case "length" : return $this->length;
      default       : return parent::getMesVar($var);
    }
  }

  public function getMessage($code){
    switch($code){
      case self::LENGTH_NOT_EQUAL         : return '%title% must contain extactly %length% characters';
      default                             : return parent::getMessage($code);
    }
  }

  public function defInit($length){
    $this->length = $length;
  }

  public function isValidCore($value){


    if(strlen((string)$value)!=$this->length)
      return $this->error(self::LENGTH_NOT_EQUAL);

    return true;

  }

}
