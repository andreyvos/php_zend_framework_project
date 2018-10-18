<?php

abstract class T3FraudDetector_ProductCondition
{

    public $id;
    public $works;
    public $typeName;
    public $settings;
    public $productid;
    protected $system;
    protected $db;
    public $haveParams = true;
    public static $availableTypes = array
	(
	'IpAddress'
    );
    public static $table = 'fraud_detector_product_conditions';

    public function __construct()
    {

	$this->system = T3System::getInstance();
	$this->db = T3Db::api();
	foreach ($this->getParamsNames() as $v)
	{
	    $this->settings[$v] = array();
	}
    }

    public static $fields = array('id', 'works', 'type_name', 'productid');

    public abstract function getTitle();

    protected abstract function accept(T3Lead $lead, T3Channel_NewLead_Abstract $channel);

    protected abstract function getTypeName();

    public function update($insert=false)
    {
	$bind = array();
	$bind['works'] = (int) $this->works;
	$bind['type_name'] = $this->getTypeName();
	$bind['productid'] = (int) $this->productid;
	foreach ($this->getParamsNames() as $oneValueName)
	{
	    $a[(string) $oneValueName] = implode(',', $this->settings[$oneValueName]);
	}
	$bind['settings'] = serialize($a);

	if ($insert)
	{
	    $this->db->insert(self::$table, $bind);
	}
	else
	{
	    $where = "id=" . $this->id;
	    $this->db->update(self::$table, $bind, $where);
	}
    }

    public static function fromType($type)
    {
	$class = "T3FraudDetector_ProductCondition_" . $type;
	$object = new $class();
	return $object;
    }

    protected abstract function getParamsNames();

    protected abstract function getParamsLabels();

    protected abstract function isMultipleValues();

    protected abstract function isMultipleParams();
    public abstract function getDescription();
    public abstract function getSettingsDescription();

    public static function getAllWorkingConditions($product=null)
    {
	
	$data = T3Db::api()->select()->from(self::$table)->where('works=?', 1);
	if(!is_null($product))
	    $data=$data->where ('productid=?',$product);

        $r = $data->query()->fetchAll();
        $result = array();
	foreach ($r as $oneData)
	{
            try{
                $condition = self::fromType($oneData['type_name']);
                $condition->fromDbArray($oneData);
                $result[] = $condition;
            
            }catch(Exception $e)
            {
                
            }
	}
        return $result;
    }

    public function fromDbArray($params)
    {

	foreach (self::$fields as $field)
	{
	    $this->$field = $params[$field];
	}
	$settings = unserialize($params['settings']);
	foreach ($this->getParamsNames() as $oneValueName)
	{

	    $this->settings[$oneValueName] = explode(',', $settings[$oneValueName]);
	}
    }

    public static function getAll()
    {
	$data = T3Db::api()->select()->from(self::$table)->query()->fetchAll();
	$result = array();
	foreach ($data as $oneData)
	{
	    $condition = self::fromType($oneData['type_name']);
	    $condition->fromDbArray($oneData);
	    $result[] = $condition;
	}
	return $result;
    }

    public function getWorksByProduct($product)
    {
	$data = T3Db::api()->select()->from(self::$table)->where('product=?', (int) $product)->where('works=?', 1)->query()->fetchAll();
	$result = array();
	foreach ($data as $oneData)
	{
	    $condition = self::fromType($oneData['type_name']);
	    $condition->fromDbArray($oneData);
	    $result[] = $condition;
	}
    }

    public static function getByProduct($product)
    {
	$data = T3Db::api()->select()->from(self::$table)->where('productid=?', (int) $product)->query()->fetchAll();
	$result = array();
	foreach ($data as $oneData)
	{
	    $condition = self::fromType($oneData['type_name']);
	    $condition->fromDbArray($oneData);
	    $result[] = $condition;
	}
	return $result;
    }

    public static function getByType($type)
    {
	$data = T3Db::api()->select()->from(self::$table)->where('type_name=?',$type)->query()->fetchAll();
	$result = array();
	foreach ($data as $oneData)
	{
	    $condition = self::fromType($oneData['type_name']);
	    $condition->fromDbArray($oneData);
	    $result[] = $condition;
	}
	return $result;
    }

    public static function getCurrentTypes()
    {
	$data = T3Db::api()->select()->from(self::$table, array('type_name'))->query()->fetchAll();
	$r = array();
	foreach ($data as $d)
	{
	    $r[] = $d['type_name'];
	}
	return $r;
    }

}

?>
