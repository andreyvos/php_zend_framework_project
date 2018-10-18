<?php

class T3Validator_Relationship extends T3Validator_InArray {

  const REL_PARENT = 'PARENT';
  const REL_SIBLING = 'SIBLING';
  const REL_FRIEND = 'FRIEND';
  const REL_RELATIVE = 'RELATIVE';

  public function getMessage($code){
    return self::INVALID_TEXT;
  }

  public function  initialize() {
    parent::defInit(array(
      self::REL_PARENT,
      self::REL_SIBLING,
      self::REL_FRIEND,
      self::REL_RELATIVE,
    ));
  }

  public function defInit(){}

}