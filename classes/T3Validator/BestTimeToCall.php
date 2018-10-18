<?php

class T3Validator_BestTimeToCall extends T3Validator_InArray {

  const CT_ANYTIME = 'anytime';
  const CT_MORNING = 'morning';
  const CT_AFTERNOON = 'afternoon';
  const CT_EVENING = 'evening';

  public function getMessage($code){
    return self::INVALID_TEXT;
  }

  public function  initialize() {

    parent::defInit(array(
      self::CT_ANYTIME,
      self::CT_MORNING,
      self::CT_AFTERNOON,
      self::CT_EVENING
    ));
  }

  public function defInit(){}

}