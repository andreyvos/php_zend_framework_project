<?php

class T3Form_Rule {

  public $form;
  public $name;
  public $group = T3Form_Abstract::DEFAULT_GROUP;
  protected $validator;

  protected $report;

  public function  __construct($name) {

    $this->report = new Report();

    $this->name = $name;

  }

  public function setForm($form){
    $this->form = $form;
  }

  public function setValidator($validator){
    $this->validator = $validator;
  }

  public function getTitle(){
    return $this->validator->getTitle();
  }

  public function setTitle($title){
    return $this->validator->setTitle($title);
  }

  public function validate($value, &$mean, $integrityCheck = true){

    $this->report->clear();

    if($value === null && !$integrityCheck)
      return $this->report;

    $this->validator->report->clear();

    if($value === null && $integrityCheck || $value === ''){
      if(!$mean[$this->group])
        return $this->report;
      else{
        $this->validator->error(T3Validator_Abstract::IS_EMPTY);
      }
    }else
      $this->validator->isValid($value);

    $this->report->merge($this->validator->report);

    return $this->report;
    
  }

  public static function parse($data){
    
  }

}
