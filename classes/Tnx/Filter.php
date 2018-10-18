<?php

class Tnx_Filter {

    public $channelId;
    public $channel;

    public $conditions = array();
    public $conditionsByType = array();
    
    public $system;
    public $database;

    public $lastReport;
    public $lastErrorCondition;

    public function  __construct() {
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();
    }

    public function conditionExists($typeName){
      return isset($this->conditionsByType[$typeName]);
    }

    public function getAvailableTypes(){
      return Tnx_Filter_Condition::$availableTypes;
    }

    public function getCondition($typeName){
      if(!isset($this->conditionsByType[$typeName])){
        $condition = Tnx_Filter_Condition::createFromTypeName($typeName);
        $this->addCondition($condition);
      }
      return $this->conditionsByType[$typeName];
    }

    public function addCondition($condition) {
        $this->conditions[] = $condition;
        $this->conditionsByType[$condition->getTypeName()] = $condition;
        $condition->filter = $this;
    }

    /**
    * Проверяет лид, по текущим фильтрам
    * 
    * @param mixed $lead
    * @return Report
    */
    public function acceptsLead($lead) {

      $report = new Report();

      $this->lastReport = $report;

      $this->lastErrorCondition = null;

      if(true){

        /* Сортировка в зависимости от приоритетов */

        $sequence = array();

        foreach($this->conditions as $v){
          $priority = $v->getPriority();
          if(!isset($sequence[$priority]))
            $sequence[$priority] = array();
          $sequence[$priority][] = $v;
        }

        for($i = Tnx_Filter_Condition::PRIORITY_SIMPLEST; $i <= 10; $i++){

          if(!isset($sequence[$i]))
            continue;

          $needToBreak = false;
          foreach($sequence[$i] as $v){
            $v->acceptsLead($lead);
            $report->merge($v->report);
            if($v->report->isError()){
              $this->lastErrorCondition = $v;
              //vvv($v->type_name);
              $needToBreak = true;
              break;
            }
          }
          if($needToBreak)
            break;

        }

      }else{

        foreach($this->conditions as $v){
            //varExport(get_class($v));
          $v->acceptsLead($lead);
          $report->merge($v->report);
          if($v->report->isError()){
            $this->lastErrorCondition = $v;
            //vvv($v->type_name);
            break;
          }
        }

      }

      return $report;

    }

    public function getTextReport(){

      if(empty($this->lastErrorCondition))
        return 'ok';

      return $this->lastErrorCondition->getTextReport();

    }

    public function toArray($tables = null) {
        $array = array();
        foreach($this->conditions as $v)
            $array[] = $v->toArray();
        return $array;
    }


    // функция saveToDatabase обновляет условия фильтра в таблице и записывает в объекты массива
    // $this->conditions новые id в соответствии с id записей в таблице
    public function saveToDatabase() {

      $columns = array();
      $array = TableDescription::get('tnx_filters')->fields;
      foreach($array as $v)
        if ($v!='id')
          $columns[] = $v;

      $array = $this->toArray();
      $insert = array();
      foreach($array as $k => $v) {
        $insert[$k] = array();
        foreach($array[$k] as $k1 => $v1)
        if ($k1!='id')
          $insert[$k][] = $v1;
      }

      $success = true;

      try{

        $this->database->beginTransaction();

        $this->database->delete('tnx_filters', 'channel_id = ' . $this->database->quote($this->channelId));
        $last = insertMultiple($this->database, 'tnx_filters', $columns, $insert);

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        $success = false;
      }

      if($success)
        foreach($this->conditions as $v)
          $v->id = $last++;

      
    }

    public function fromArray(&$array) {
        foreach($array as &$v) {
            $condition = Tnx_Filter_Condition::createFromArray($v);
            $this->addCondition($condition);
        }
        $first = reset($array);
        $this->id = $first['id'];
    }

    public function fromDatabase($channelId = null) {
      if (!is_null($channelId))
        $this->channelId = $channelId;
      $conditions = Tnx_Filters::getInstance()->getConditions_Array($this->channelId);
      $this->fromArray($conditions);
    }

    public static function createFromArray(&$array) {
        $object = new self();
        $object->fromArray($array);
        return $object;
    }

    public static function createFromDatabase($channelId) {
        $object = new self();
        $object->fromDatabase($channelId);
        return $object;
    }

}