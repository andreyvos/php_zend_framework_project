<?php

class T3Validator_DateInFuture extends T3Validator_Date{

  const OUTDATED = 'outdated';

  public function isValidCore($value){

    parent::isValidCore($value);
    if($this->report->isError())
      return;

    $base = new Zend_Date($value);

    if($base->compare(Zend_Date::now()) != 1){
      $this->error(self::OUTDATED);
      return;
    }

  }


}
