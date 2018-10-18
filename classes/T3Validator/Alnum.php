<?php

class T3Validator_Alnum extends T3Validator_Abstract{

    const NOT_ALNUM = 'notAlnum';

    public $allowWhiteSpace;

    public function getMessage($code){
      switch($code){
        case self::NOT_ALNUM      : return '%title% must contain only digits and letters';
        default                   : return parent::getMessage($code);
      }
    }

    public function defInit($allowWhiteSpace = false){
      $this->allowWhiteSpace = (boolean) $allowWhiteSpace;
    }

    public function isValidCore($value){
      $valueString = (string) $value;

      $whiteSpace = $this->allowWhiteSpace ? '\s*' : '';

      if(!preg_match("/^{$whiteSpace}[A-Za-z0-9]+{$whiteSpace}$/i", $valueString)){
        $this->error(self::NOT_ALNUM);
        return false;
      }

      return true;

    }

}
