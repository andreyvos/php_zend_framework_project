<?php

class T3Validator_NotLessThan extends T3Validator_Abstract{

  const FORMAT = 'format';
  const LIMIT = 'greater';

  public $limit = 0;

  public function defInit($limit){
    $this->limit = $limit;
  }

  public function isValidCore($value){

    if(!is_numeric($value)){
      $this->error(self::FORMAT);
      return;
    }

    if((float)$value<$this->limit){
      $this->error(self::LIMIT);
      return;
    }


  }

}