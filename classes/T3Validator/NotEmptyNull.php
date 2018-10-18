<?php

class T3Validator_NotEmptyNull extends T3Validator_Abstract{

  public function  initialize() {
    $this->canBeNull = false;
    $this->canBeEmptyString = false;
  }

}