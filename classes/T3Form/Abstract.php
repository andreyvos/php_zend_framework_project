<?php

abstract class T3Form_Abstract {

  const DEFAULT_GROUP = 'defaultGroup';

  public $name;
  public $rules;

  public $report;

  public $groups = array();
  public $groupsScheme = array(self::DEFAULT_GROUP => null);


  public function  __construct() {
    $this->report = new Report();
    $this->setGroupsScheme($this->groupsScheme);
    $this->initialize();
  }

  abstract protected function initialize();

  public function validate($data, $integrityCheck = true){

    $mean = $this->defineGroupsMean();

    $this->cleanUpGroupsMean($mean);

    $this->report->clear();

    ExpressValidator::nowVerifying($data, $this->name);

    foreach($this->rules as $key => $rule){
      $value = isset($data[$key]) ? $data[$key] : null;
      $this->report->merge($rule->validate($value, $mean, $integrityCheck));
    }

    return $this->report;

  }

  public function addRule($rule){
    $rule->setForm($this);
    $this->rules[$rule->name] = $rule;
  }

  public function setGroupsScheme($scheme){
    $this->groupsScheme = $scheme;
    $ar = array();
    foreach($this->groupsScheme as $child => $parent){
      if($parent !== null)
        $ar[$parent] = true;
      $ar[$child] = true;
    }
    $this->groups = array_keys($ar);
  }

  protected function cleanUpGroupsMean(&$mean){
    $b1 = true;
    while($b1){
      $b1 = false;
      foreach($this->groupsScheme as $child => $parent)
        if($parent !== null && !$mean[$parent] && $mean[$child]){
          $mean[$child] = false;
          $b1 = true;
        }
    }
  }

  public function defineGroupsMean(){
    $ar = array();
    foreach($this->groups as $group)
      $ar[$group] = true;
    return $ar;
  }

  public static function parse($data){

  }

  protected static function parseGroupsScheme($data){

  }

}