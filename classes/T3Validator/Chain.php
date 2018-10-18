<?php

class T3Validator_Chain extends T3Validator_Abstract{

  protected $validators = array();
  protected $breakOnError = true;

  public function addValidator($validator){

    $validator->selfReliant = false;

    if($this->keyName===null && $validator->keyName!==null)
      $this->keyName = $validator->keyName;

    if($this->title===null && $validator->title!==null)
      $this->title = $validator->title;

    $validator->setKeyName($this->keyName);
    $validator->setTitle($this->title);
    $validator->setInsertText($this->insertText);    

    $this->validators[] = $validator;

  }

  public function setInsertText($insertText){
    foreach($this->validators as $v)
      $v->setInsertText($insertText);
    $this->insertText = $insertText;
  }

  public function setTitle($title){
    foreach($this->validators as $v)
      $v->setTitle($title);
    $this->title = $title;
  }

  public function setKeyName($keyName){
    foreach($this->validators as $v)
      $v->setKeyName($keyName);
    $this->keyName = $keyName;
  }


  protected function mesVars(){
    parent::mesVars();
    foreach($this->validators as $v)
      $v->mesVars();
  }

  protected function getMesVar($code){
    $var = parent::getMesVar($code);
    if($var !== T3Validator_Abstract::NOT_EXISTENT_MES)
      return $var;
    foreach($this->validators as $v){
      $var = $v->getMesVar($code);
      if($var!==T3Validator_Abstract::NOT_EXISTENT_MES)
        return $var;
    }
    return T3Validator_Abstract::NOT_EXISTENT_MES;
  }

  protected function getMessage($code){
    $var = parent::getMessage($code);
    if($var !== T3Validator_Abstract::NOT_EXISTENT_MES)
      return $var;
    foreach($this->validators as $v){
      $var = $v->getMessage($code);
      if($var!==T3Validator_Abstract::NOT_EXISTENT_MES)
        return $var;
    }
    return T3Validator_Abstract::NOT_EXISTENT_MES;
  }

  public function isValidCore($value){
    foreach($this->validators as $v){
      $r = $v->isValid($value);
      $this->report->merge($r);
      if($this->breakOnError && $r->isError())
        break;
    }
  }

}