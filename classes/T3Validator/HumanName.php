<?php

class T3Validator_HumanName extends T3Validator_Regex {

  public function initialize() {
    parent::defInit("/^([a-zA-Z]|\-|\'|\`|\.){1,128}$/");
  }

  public function defInit(){}

}