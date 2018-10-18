<?php

TableDescription::addTable('fraud_detectors_conditions', array(
  'id',
  'product_id',
  'type_name',
  'affirmative',
  'works',
  'misc',
));

abstract class T3FraudDetector_Condition extends DbSerializable{

  const CHANGE_OFFER = 'changeOffer';
  const FILTER_VIOLATED = 'filterViolated';  

  public static $availableTypes = array(
    'FirstName','IP','LastName'
  );

  const DefaultSeparator = ",";

  public $id;
  public $product_id;
  public $type_name;
  public $affirmative = 1;
  public $works = 0;
  public $misc = '';

  public $filter;


  public $report;
  
  public $lastVerifiedLead;

  public static function CollapseSpaces($s){
    return trim(preg_replace('/\s+/i', ' ', $s));
  }





  public function  __construct() {

    parent::__construct();
    $this->tables[] = 'fraud_detectors_conditions';
    $this->report = new Report();

  }

  public abstract function getTitle();

  public abstract function getTypeName();

  public function toArray($tables = null){
    if(is_null($this->product_id) && !is_null($this->filter))
      $this->product_id = $this->filter->product_id;
    $this->affirmative = (int)$this->affirmative;
    $this->works = (int)$this->works;
    $this->type_name = $this->getTypeName();
    return parent::toArray($tables);
  }

  public function fromArray(&$array){
    parent::fromArray($array);
    $this->affirmative = (int)$this->affirmative;
    $this->works = (int)$this->works;
  }

  public function acceptsLead(T3Lead $lead){

    $this->lastVerifiedLead = $lead;

    $this->report->clear();

    if(!$this->works){
      $this->report->ok($this->getTypeName());
      return $this->report->isNoError();
    }

    if($this->acceptsLeadStraight($lead) == $this->affirmative)
      $this->report->ok($this->getTypeName());
    else
      $this->report->error($this->getTypeName(), self::FILTER_VIOLATED);

    return $this->report->isNoError();

  }

  protected function changeOffer($newValue){
    $this->report->notice($this->getTypeName(), self::CHANGE_OFFER)->setData('newValue', $newValue);
  }

  protected abstract function acceptsLeadStraight(T3Lead $lead);
  public static function createFromTypeName($typeName){
    $class = "T3FraudDetector_Condition_$typeName";
    $object = new $class();
    return $object;
  }

  public static function createFromDatabase($conditions){
    $array = self::fromDatabaseStatic('fraud_detectors_conditions', $conditions);
    $object = self::createFromArray($array);
    return $object;
  }

  public static function createFromArray(&$array){
    $object = self::createFromTypeName($array['type_name']);
    $object->fromArray($array);
    return $object;
  }

  /*protected function acceptsNumber($number){
    switch(trim($this->misc)){
      case '<':
        return $this->number_value> $number;
      case '<=':
        return $this->number_value>=$number;
      case '>':
        return $this->number_value< $number;
      case '>=':
        return $this->number_value<=$number;
      case '==':
        return $this->number_value==$number;
      case '!=':
        return $this->number_value!=$number;
    }
    throw new Exception('number_relation_type has an invalid value');
  }*/

  /*protected function callMethod($object, $methodName){
    return call_user_func_array(array($object, $methodName), $this->methodParametersArray);
  }*/

  public function getLeadsAcceptedByChannel($channelId){
    return T3BuyerChannel::getLeadsAcceptedByChannel($channelId);
  }

  public function getActualFieldName(){
    return false;
  }

  public function getActualValue(T3Lead $lead){
    return false;
  }

  public function getTextReport(){
    $field = $this->getActualFieldName();
    if(empty($field)){
      $field = '_there_is_no_actual_field_';
      $value = '_there_is_no_actual_field_';
    }else{
      $value = $this->getActualValue($this->lastVerifiedLead);
    }
    return "Filter: {$this->type_name}; Field: {$field}; Value: {$value};";
  }

  /*protected function getPropertyOrMethod($lead, $name){

        if  (property_exists($lead, $name))
      return $lead->$name;

    elseif  (property_exists($lead->body, $name))
      return $lead->body->$name;

    elseif  (method_exists($lead, $name))
      return $this->callMethod($lead,$name);

    elseif  (method_exists($lead->body, $name))
      return $this->callMethod($lead->body,$name);

    elseif  (method_exists($this, $name))
      return $this->callMethod($this,$name);

    throw new Exception('T3FraudDetector_Condition getPropertyOrMethod $name invalid');
    
  }*/

}