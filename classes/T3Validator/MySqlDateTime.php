<?php

class T3Validator_MySqlDateTime extends T3Validator_Abstract{

  const TYPE = 'type';
  const FORMAT = 'format';
  const DATE = 'date';
  const TIME = 'time';


  public function defInit(){}

  public function isValidCore($value){

    if($value === null)
      return true;
    
    if(!is_string($value)){
      $this->error(self::TYPE);
      return false;
    }

    if(!preg_match('/(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})/', $value, $ar)){
      $this->error(self::FORMAT);
      return false;
    }

    if(!checkdate($ar[1], $ar[2], $ar[0])){
      $this->error(self::DATE);
      return false;
    }

    if($ar[3]>24 || $ar[4]>60 || $ar[5]>60){
      $this->error(self::TIME);
      return false;
    }

    return true;
      
  }

}