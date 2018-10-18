<?php

TableDescription::addTable('users_buyer_agents', array(
    'id',

    'contactPhone',
    'contactEmail',
    'contactAIM',
    'contactSkype',
    'contactICQ',
    'contactYahoo',
	'bonus_percent'

));  

class T3UserBuyerAgent extends DbSerializable {
    public $id;



    public $contactPhone;
    public $contactEmail;
    public $contactAIM;
    public $contactSkype;
    public $contactICQ;
    public $contactYahoo;
    public $bonus_percent;


    /**
    * Получение объекта пользователя
    * @return T3User
    */
    public function getUser(){
        return T3Users::getUserById($this->id);
    }

    public function  __construct() {

	    if (!isset($this->className)) $this->className = __CLASS__;
	    parent::__construct();
	    $this->tables = array('users_buyer_agents');
        $this->readNewIdAfterInserting = false;
    }
   
  
    public function getUsers() {
	    $users = array(); 
        /*
        $select = $this->database->select()->from(array("b" => "users_company_buyer"))->where('agentID=?',(int)$this->id);
	    $comps = $this->database->query($select)->fetchAll();
	    $users = array();
        foreach($comps as $v) {
	        $select = $this->database->select()->from(array("u"=>"users"))->where("company_id=?",(int)$v["id"])->where('role=?','buyer');
	        $usersInCompany = $this->database->query($select)->fetchAll();
	    
            foreach($usersInCompany as $v) {
		        $users[]=$v;
	        }
	    } 
        */
	    return $users;
    }
    
    public function setProduct($product, $action = true, $type = null){
        $action = (bool)$action;  
        
        if($action){
            if(is_null($type)){
                T3Db::api()->insert('users_buyer_agents_products', array(
                    'id'        => $this->id,
                    'product'   => $product,
                ));
            }
            else {
                T3Db::api()->insert('users_buyer_agents_products', array(
                    'id'        => $this->id,
                    'product'   => $product,
                    'type'      => $type,
                ));    
            }
        }
        else {
            if(is_null($type)){
                T3Db::api()->delete('users_buyer_agents_products', "id={$this->id} and product=" . T3Db::api()->quote($product));
            }
            else {
                T3Db::api()->delete('users_buyer_agents_products', "id={$this->id} and product=" . T3Db::api()->quote($product) . " and `type`=" . T3Db::api()->quote($type));    
            }
        } 
    }
    
    
    static public function getBuyerAgent($buyer_id, $product)
    {
    	$agent = (int)T3Db::api()->fetchOne("SELECT agent_id FROM buyer_channels_agents WHERE buyer_id = ".$buyer_id." AND product = '".$product."' ");
    	if($agent) return $agent;
    	return 0;
    }
    
    
    /*
     * @return array of buyer agent channels 
     * */
    static public function getBuyerAgentChannels( $buyerAgentId )
    {
    	
    	$postingsOut = array();
    	
    	$postingsAll = T3Db::api()->fetchAll("select id, product, buyer_id from buyers_channels");
    	$postingsIndex = array();
    	if(count($postingsAll)){
    		foreach($postingsAll as $el){
    			if(!isset($postingsIndex[$el['product']][$el['buyer_id']])) $postingsIndex[$el['product']][$el['buyer_id']] = array();
    			$postingsIndex[$el['product']][$el['buyer_id']][] = $el['id'];
    		}
    	}
    	
    	//varDump2($postingsIndex);
    	
    	$buyerAgentId = (int)$buyerAgentId;
    	$relationsAll = T3Db::api()->fetchAll("SELECT * FROM buyer_channels_agents WHERE agent_id = " . $buyerAgentId);
    	
    	//varDump2($relationsAll);
    	
    	
    	$agents = array();
    	if(count($relationsAll)){
    		foreach($relationsAll as $el){
    			if(isset($postingsIndex[$el['product']][$el['buyer_id']])){
    				foreach($postingsIndex[$el['product']][$el['buyer_id']] as $posting){
    					$postingsOut[] = $posting;
    				}
    			}
    		}
    	}
    	
    	//varDump2($postingsOut);
    	return $postingsOut;
    	/// 
    	
    	
    	
    	$buyerAgentId = (int)$buyerAgentId;
    	$buer_agent_ch = T3Db::api()->fetchAll("SELECT * FROM buyer_channels_agents WHERE agent_id = " . $buyerAgentId);
    	//varDump2($buer_agent_ch);
    	$buyers = array();
    	$products = array();
    	$as_key = array();
    	
    	$prod_buyers = array();
    	
    	foreach ( $buer_agent_ch AS $bch ) {
    		$buyers[] = $bch['buyer_id'];
    		$products[] = $bch['product'];
    		$as_key[$bch['buyer_id']] = $bch['product'];
			$prod_buyers[$bch['product']][] = $bch['buyer_id'];
    	}
    	
    	//varDump2($prod_buyers);
    	
    	$all_channels = T3Db::api()->fetchAll("SELECT * FROM buyers_channels WHERE isDeleted = '0' ");
    	
    	$postingsOut = array();
    	
    	foreach ( $all_channels AS $cnData ) {
    		
    		if(isset($prod_buyers[$cnData['product']]) && is_array($prod_buyers[$cnData['product']]) && ( in_array($cnData['buyer_id'], $prod_buyers[$cnData['product']]) ) )
    		$postingsOut[] = $cnData['id'];
    		
    		/*
    		if( isset($as_key[$cnData['buyer_id']]) && $as_key[$cnData['buyer_id']] == $cnData['product']) {
    			$postingsOut[] = $cnData['id'];
    		}
    		*/
    		/*
			if( in_array($cnData['buyer_id'], $buyers) ) {
				if( in_array($cnData['product'], $products) )
				$postingsOut[] = $cnData['id'];
			}
			*/
			
    	}
    	
    	//varDump2($postingsOut);
    	return $postingsOut;
    	
    }
    
  

}

