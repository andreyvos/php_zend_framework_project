<?php

class T3FraudDetector{
    protected $conditions;

    public function __construct(){

    }

    public function check(T3Lead $lead, T3Channel_NewLead_Abstract $channel){
    	if(isset($lead->data_email))
    	{
    		$fraudDomains = array(
    								"hushmail.com",
									"hushmail.me",
									"hush.com",
									"hush.ai",
									"mac.hush.com"
								);
    		$domain = array_pop(explode('@',$lead->data_email));
    		if(in_array($domain, $fraudDomains))
    		{
    			return array('valid' => false, "wm_comment" => "", "admin_comment" => "hushmail domain");
    		}

    	}


	    $c = $this->getGlobalConditions();
	    foreach ($c as $oneC){
	        if ($oneC->works){
		        $r = $oneC->accept($lead, $channel);
		        if (!$r['valid'])
		            return $r;
	        }
	    }
	    unset($c);

        $c = T3FraudDetector_ProductCondition::getAllWorkingConditions(T3Products::getID($lead->product));
	    if (!is_null($c) && is_array($c)){
	        foreach ($c as $oneC){
		        $r = $oneC->accept($lead, $channel);
		        if (!$r['valid'])
		            return $r;
	        }
	    }
	    return array('valid' => true, 'comment' => "");
    }

    public function install(){
	    T3Db::api()->delete(T3FraudDetector_GlobalCondition::$table);
	    $globalConditionsTypes = T3FraudDetector_GlobalCondition::$availableTypes;
	    foreach ($globalConditionsTypes as $globalConditionType){
	        $class = "T3FraudDetector_GlobalCondition_" . $globalConditionType;
	        $object = new $class();
	        $object->update(true);
	    }

	    T3Db::api()->delete(T3FraudDetector_ProductCondition::$table);
	    $productConditionsTypes = T3FraudDetector_ProductCondition::$availableTypes;

	    foreach (T3Products::getProducts() as $product){
	        foreach ($productConditionsTypes as $productConditionsType){
		        $object = T3FraudDetector_ProductCondition::fromType($productConditionsType);
		        $object->productid = $product['id'];
		        $object->works = 0;
		        $object->update(true);
	        }
	    }
    }

    public function update(){
	    $current = T3FraudDetector_GlobalCondition::getCurrentTypes();
	    $all = T3FraudDetector_GlobalCondition::$availableTypes;
	    $new = array_diff($all, array_values($current));

        foreach ($new as $oneNew){
	        $class = "T3FraudDetector_GlobalCondition_" . $oneNew;
	        $object = new $class();
	        $object->update(true);
	    }
    }

    public function getGlobalConditions(){
	    $c = T3FraudDetector_GlobalCondition::getAll();
	    return $c;
    }

    public function getProductsConditions(){
	    $t = T3FraudDetector_ProductCondition::$availableTypes;
	    $c = array();
	    foreach ($t as $oneT){
	        $c[$oneT] = T3FraudDetector_ProductCondition::getByType($oneT);
	    }
	    return $c;
    }
}
