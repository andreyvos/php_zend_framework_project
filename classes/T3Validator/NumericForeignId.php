<?php

class T3Validator_NumericForeignId extends T3Validator_Chain{

  public function  initialize() {

    $this->addValidator(new T3Validator_Int());

    $greater = new T3Validator_GreaterThan();
    $greater->defInit(0);
    $this->addValidator($greater);
  }


}