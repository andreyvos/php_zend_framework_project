<?php

class T3Validator_Logic extends T3Validator_InArray {

  public function  initialize() {
    parent::defInit(array('0', '1', 'true','false', 0, 1, true, false));
  }

  public function defInit(){}

}