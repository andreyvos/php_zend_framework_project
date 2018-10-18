<?php

class T3Validator_Alpha extends T3Validator_Abstract{

    const NOT_ALPHA = 'notAlpha';

    public $allowWhiteSpace;

    public function getMessage($code){
      switch($code){
        case self::NOT_ALPHA : return '%title% must contain only letters';
        default              : return parent::getMessage($code);
      }
    }

    public function defInit($allowWhiteSpace = false){
      $this->allowWhiteSpace = (boolean) $allowWhiteSpace;
    }

    public function isValidCore($value){
      $valueString = (string) $value;

      $whiteSpace = $this->allowWhiteSpace ? '\s*' : '';

      if(!preg_match("/^{$whiteSpace}[A-Za-z]+{$whiteSpace}$/i", $valueString)){
        $this->error(self::NOT_ALPHA);
        return false;
      }

      return true;

    }

}
