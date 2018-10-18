<?php


class T3Validator_PhoneExt extends T3Validator_Chain {

  public function initialize() {

    $v = new T3Validator_StringLength();
    $v->defInit(1, 5);
    $this->addValidator($v);

    $this->addValidator(new T3Validator_Digits());

  }

}