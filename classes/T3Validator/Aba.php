<?php

class T3Validator_Aba extends T3Validator_Abstract{

  const INVALID_NUMBER = 'invalidNumber';
  const INVALID_LENGTH = 'invalidLength';


  public function getMessage($code){
    switch($code){
      case self::INVALID_NUMBER : return self::INVALID_TEXT;
      case self::INVALID_LENGTH : return '%title% must have 9 digits';
      default                   : return parent::getMessage($code);
    }
  }
  
  public function isValidCore($value){

    $value = (string)$value;

    if(strlen($value)!=9){
      $this->error(self::INVALID_LENGTH);
      return false;
    }

    if(preg_match('/^[0-9]{9}$/i',$value)){

      $n = 0;
      for ($i = 0; $i < 9; $i += 3)
        $n += $value[$i]*3 + $value[$i+1]*7 + $value[$i+2];

      if($n == 0 || $n % 10 != 0){
        $this->error(self::INVALID_NUMBER);
        return false;
      }
      
    } else {
      $this->error(self::INVALID_NUMBER);
      return false;
    }

    return true;

  }

}