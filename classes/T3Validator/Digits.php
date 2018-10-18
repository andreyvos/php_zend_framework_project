<?php


class T3Validator_Digits extends T3Validator_Abstract{

    const NOT_DIGITS = 'notDigits';

    protected $allowWhiteSpace;

    public function getMessage($code){
      switch($code){
        case self::NOT_DIGITS    : return '%title% must contain only digits';
        default                  : return parent::getMessage($code);
      }
    }

    public function defInit($allowWhiteSpace = false){
      $this->allowWhiteSpace = (boolean) $allowWhiteSpace;
    }

    public function isValidCore($value){
      $valueString = (string) $value;

      $whiteSpace = $this->allowWhiteSpace ? '\s*' : '';

      if(!preg_match("/^{$whiteSpace}[0-9]+{$whiteSpace}$/i", $valueString)){
        $this->error(self::NOT_DIGITS);
        return false;
      }

      return true;

    }

}
