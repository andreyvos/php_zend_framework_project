<?php

abstract class T3FraudDetector_GlobalCondition {
    public $id;
    public $works;
    public $typeName;
    public $settings;
    protected $system;
    protected $db;
    public $haveParams = true;
    public static $availableTypes = array(
	    'Dublicate','ApplicantInfo','Email','Phone','IpAddress','SSN','WebmasterIP'
    );
    
    public static $table = 'fraud_detector_global_conditions';

    public function __construct(){
	    $this->system = T3System::getInstance();
	    $this->db = T3Db::api();
	    foreach ($this->getParamsNames() as $v){
	        $this->settings[$v] =  array();
	    }
    }
    public static $fields = array('id', 'works', 'type_name');

    public abstract function getTitle();

    protected abstract function accept(T3Lead $lead, T3Channel_NewLead_Abstract $channel);

    protected abstract function getTypeName();

    public function update($insert=false){
	    $bind = array();
	    $bind['works'] = (int) $this->works;
	    $bind['type_name'] = $this->getTypeName();
	    
        foreach ($this->getParamsNames() as $oneValueName){
	       $a[(string) $oneValueName] = implode(',',$this->settings[$oneValueName]);
	    }
        
	    $bind['settings'] = serialize($a);

	    if ($insert){
	        $this->db->insert(self::$table, $bind);
	    }
	    else{
	        $where = "id=" . $this->id;
	        $this->db->update(self::$table, $bind, $where);
	    }
    }

    public static function fromType($type){
	    $class = "T3FraudDetector_GlobalCondition_" . $type;
	    $object = new $class();
	    return $object;
    }

    protected abstract function getParamsNames();
    protected abstract function getParamsLabels();
    protected abstract function isMultipleValues();
    protected abstract function isMultipleParams();
    public abstract function getDescription();
    public abstract function getSettingsDescription();
    
    public static function getAllWorkingConditions(){
	    $data = $this->db->select()->from(self::$table)->where('works=', 1);
    }

    public function fromDbArray($params){
	    foreach (self::$fields as $field){
	        $this->$field = $params[$field];
	    }
        
	    $settings = unserialize($params['settings']);
	    foreach ($this->getParamsNames() as $oneValueName){
		    $this->settings[$oneValueName] = explode(',', $settings[$oneValueName]);
        }   
    }

    public static function getAll(){
	    $data = T3Db::api()->select()->from(self::$table)->query()->fetchAll();
	    $result = array();
	    foreach ($data as $oneData){
	        $condition = self::fromType($oneData['type_name']);
	        $condition->fromDbArray($oneData);
	        $result[] = $condition;
	    }
	    return $result;
    }
    
    public static function getCurrentTypes(){
	    $data = T3Db::api()->select()->from(self::$table,array('type_name'))->query()->fetchAll();
	    $r = array();
	    foreach ($data as $d){
	        $r[]=$d['type_name'];
	    }
	    return $r;
    }

}

function filter(&$value){
    $value=trim(strtolower($value));
}