<?php

class T3Validator_Float extends T3Validator_Abstract{

    const NOT_FLOAT = 'notFloat';

    public function getMessage($code){
      switch($code){
        case self::NOT_FLOAT : return '%value% is not a number';
        case self::INVALID   : return '%value% is not a number';
        default              : return parent::getMessage($code);
      }
    }

    public function isValidCore($value){
        $valueString = (string) $value;


        $locale = localeconv();

        $valueFiltered = str_replace($locale['thousands_sep'], '', $valueString);
        $valueFiltered = str_replace($locale['decimal_point'], '.', $valueFiltered);

        if (strval(floatval($valueFiltered)) != $valueFiltered) {
            $this->error();
            return false;
        }

        return true;
    }

}
