<?php

class T3Validator_PayFrequency extends T3Validator_InArray {

  const FR_WEEKLY = 'WEEKLY';
  const FR_BIWEEKLY = 'BIWEEKLY';
  const FR_TWICEMONTHLY = 'TWICEMONTHLY';
  const FR_MONTHLY = 'MONTHLY';

  public function getMessage($code){
    return self::INVALID_TEXT;
  }

  public function  initialize() {
    parent::defInit(array(
      self::FR_WEEKLY,
      self::FR_BIWEEKLY,
      self::FR_TWICEMONTHLY,
      self::FR_MONTHLY,
    ));
  }

  public function defInit(){}

}