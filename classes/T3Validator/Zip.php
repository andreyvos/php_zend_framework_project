<?php

class T3Validator_Zip extends T3Validator_Chain {

  public function  initialize() {

    $v = new T3Validator_ExactLength();
    $v->defInit(5);
    $this->addValidator($v);

    $this->addValidator(new T3Validator_Digits());

  }

}