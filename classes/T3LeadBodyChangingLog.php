<?php

class T3LeadBodyChangingLog {

  protected static $_instance;

  public $database;

  public $currentRecord;
  public $buffer = array();

  protected function initialize() {
    $this->database = T3Db::api();
  }

  public static function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self();
      self::$_instance->initialize();
    }
    return self::$_instance;
  }

  public function beginRecord($leadId, $field, $value){
    $this->currentRecord = new T3LeadBodyChangingLogItem();
    $this->currentRecord->lead_id = $leadId;
    $this->currentRecord->field_name = $field;
    $this->currentRecord->initial_value = $value;
  }

  public function finishRecord($value, $onlyIfValuesDiffer = true){
    if($onlyIfValuesDiffer && $this->currentRecord->initial_value == $value)
      return false;
    $this->currentRecord->final_value = $value;
    $this->currentRecord->setDatetimeToNow();
    $this->currentRecord->setCurrentUserAsAuthor();
    $this->buffer[] = $this->currentRecord->toArray();
    return true;
  }

  public function record($leadId, $field, $initialValue, $finalValue, $onlyIfValuesDiffer = true){
    if($onlyIfValuesDiffer && $initialValue == $finalValue)
      return false;
    $this->beginRecord($leadId, $field, $initialValue);
    $this->finishRecord($finalValue);
    return true;
  }

  public function change(&$leadBody, $field, $finalValue, $onlyIfValuesDiffer = true){
    if(is_array($leadBody)){
      if($onlyIfValuesDiffer && $ar[$field] == $finalValue)
        return false;
      $this->beginRecord($ar['id'], $field, $ar[$field]);
      $ar[$field] = $finalValue;
      $this->finishRecord($finalValue);
    }else{
      if($onlyIfValuesDiffer && $ar->$field == $finalValue)
        return false;
      $this->beginRecord($ar->id, $field, $leadBody->$field);
      $leadBody->$field = $finalValue;
      $this->finishRecord($finalValue);
    }
    return true;
  }

  public function recordArray($initialLeadBody, $finalLeadBody, $onlyIfValuesDiffer = true){
    $result = 0;
    foreach($initialLeadBody as $k => $v)
      if($k!='id')
        $result += (int)($this->record($initialLeadBody['id'], $k, $initialLeadBody[$k], $finalLeadBody[$k], $onlyIfValuesDiffer));
    $this->flush();
    return $result;
  }

  public function clearBuffer(){
    $this->buffer = array();
  }

  public function flush(){
    $ar = array();
    foreach($this->buffer as $k => $v)
      unset($this->buffer[$k]['id']);
    insertMultiple($this->database, 'leads_body_changing_log',
      array('lead_id', 'author_id', 'changing_datetime', 'field_name', 'initial_value', 'final_value'),
      $this->buffer);
    $this->buffer = array();
  }

}