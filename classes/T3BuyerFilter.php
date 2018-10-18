<?php

class T3BuyerFilter {
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
        return T3BuyerFilter_Condition::$availableTypes;
    }

    public function getCondition($typeName){
        if(!isset($this->conditionsByType[$typeName])){
            $condition = T3BuyerFilter_Condition::createFromTypeName($typeName);
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
    public function acceptsLead($lead, $excludedFilters = array()) {
        $report = new Report();

        $this->lastReport = $report;

        $this->lastErrorCondition = null;

        /* Сортировка в зависимости от приоритетов */
        $sequence = array();

        // varExport($this->conditions);

        foreach($this->conditions as $v){
            if(!is_array($excludedFilters) || !count($excludedFilters) || !in_array($v->type_name, $excludedFilters)){
                $priority = $v->getPriority();

                if(!isset($sequence[$priority])){
                    $sequence[$priority] = array();
                }

                $sequence[$priority][] = $v;
            }
        }

        for($i = T3BuyerFilter_Condition::PRIORITY_SIMPLEST; $i <= 10; $i++){
            if(!isset($sequence[$i])){
                continue;
            }

            $needToBreak = false;
            
            foreach($sequence[$i] as $v){
                $v->acceptsLead($lead);
                $report->merge($v->report);
                if($v->report->isError()){
                    $this->lastErrorCondition = $v;
                    $needToBreak = true;
                    break;
                }
            }
            
            if($needToBreak) break;
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
        $array = TableDescription::get('buyers_filters_conditions')->fields;
        
        foreach($array as $v){
            if ($v!='id'){
                $columns[] = $v;
            }
        }

        $array = $this->toArray();
        $insert = array();
        
        foreach($array as $k => $v) {
            $insert[$k] = array();
            foreach($array[$k] as $k1 => $v1){
                if ($k1!='id'){
                    $insert[$k][] = $v1;
                }
            }
        }

        $success = true;

        try{

            $this->database->beginTransaction();



            $this->database->delete('buyers_filters_conditions', 'channel_id = ' . $this->database->quote($this->channelId));
            $last = insertMultiple($this->database, 'buyers_filters_conditions', $columns, $insert);

            $this->database->commit();

        }
        catch(Exception $e){
            $this->database->rollBack();
            $success = false;
        }

        if($success){
            foreach($this->conditions as $v){
                $v->id = $last++;
            }
        } 
    }

    public function fromArray(&$array) {
        foreach($array as &$v) {
            $condition = T3BuyerFilter_Condition::createFromArray($v);
            $this->addCondition($condition);
        }
        $first = reset($array);
        $this->id = $first['id'];
    }

    public function fromDatabase($channelId = null, $minimization = false) {
        if (!is_null($channelId)){
            $this->channelId = $channelId;
        }

        $conditions = T3BuyerFilters::getInstance()->getConditions_Array($this->channelId, $minimization);

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
