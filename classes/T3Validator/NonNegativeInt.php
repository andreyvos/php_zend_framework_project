<?php


class T3Validator_NonNegativeInt extends T3Validator_Chain{

  public function initialize() {
    $int = new T3Validator_Int();
    $nonNegative = new T3Validator_GreaterThan();
    $nonNegative->defInit(-1);
    $this->addValidator($int);
    $this->addValidator($nonNegative);    
  }

}
