<?php

class T3Validator_NotEmpty extends T3Validator_Abstract{

  public function  initialize() {
    $this->canBeEmptyString = false;
  }

}
