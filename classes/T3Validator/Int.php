<?php


class T3Validator_Int extends T3Validator_Abstract{

    const NOT_INT = 'notInt';

    public function getMessage($code){
      switch($code){
        case self::NOT_INT        : return '%title% is not an integer number';
        case self::INVALID        : return '%title% is not an integer number';
        default                   : return parent::getMessage($code);
      }
    }

    public function isValidCore($value){

        $valueString = (string) $value;


        $locale = localeconv();

        $valueFiltered = str_replace($locale['decimal_point'], '.', $valueString);
        $valueFiltered = str_replace($locale['thousands_sep'], '', $valueFiltered);

        if (strval(intval($valueFiltered)) != $valueFiltered) {
            $this->error(self::NOT_INT);
            return false;
        }

        return true;
    }

}
