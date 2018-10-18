<?php

class T3Validator_PayDate2 extends T3Validator_Date{

  const FORMAT = 'format';

  public function isValidCore($value){

    parent::isValidCore($value);
    if($this->report->isError())
      return;

    $dateString = ExpressValidator::getNowVerifying('pay_date1');
    if(Zend_Date::isDate($dateString, DEFAULT_DATE_FORMAT))
      $base = new Zend_Date($dateString);
    else
      return;

    $date = new Zend_Date($value, DEFAULT_DATE_FORMAT);

    $date->sub($base);
    $d = $date->get(Zend_Date::DAY);

    $freq = strtoupper(ExpressValidator::getNowVerifying('pay_frequency'));
    if(
      $freq!=T3Validator_PayFrequency::FR_WEEKLY &&
      $freq!=T3Validator_PayFrequency::FR_BIWEEKLY &&
      $freq!=T3Validator_PayFrequency::FR_TWICEMONTHLY &&
      $freq!=T3Validator_PayFrequency::FR_MONTHLY)
    return;

    $result =
      $freq==T3Validator_PayFrequency::FR_WEEKLY        && $d>=5  && $d<=9  ||
      $freq==T3Validator_PayFrequency::FR_BIWEEKLY      && $d>=12 && $d<=16 ||
      $freq==T3Validator_PayFrequency::FR_TWICEMONTHLY  && $d>=11 && $d<=19 ||
      $freq==T3Validator_PayFrequency::FR_MONTHLY       && $d>=26 && $d<=33;

    if(!$result){
      $this->error(self::INVALID);
      return;
    }

  }


}
