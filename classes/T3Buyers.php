<?php

class T3Buyers {

    private $database;
    
    private static $_instance = null;
    
    static public $namesCache = array();

    static public $BuyerProductAgents = null;
    static public $buyers_Agents = null;
    
    
    public function __construct(){
        $this->database = T3Db::api();    
    }
    
    /**
    * Возвращает объект класса T3Buyers
    * @return T3Buyers
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
       
    public static function getBuyersArray($cols = null, $condition = null, $order = null){
        $select = new Zend_Db_Select(self::getInstance()->database);
        $select->from('users_company_buyer', $cols);
        
        if($condition)$select->where($condition);
        if($order)$select->order($order);   
        
        return self::getInstance()->database->fetchAll($select, null, Zend_DB::FETCH_ASSOC);  
    }      

    public static function getBuyersAndChannelsForProducts($product){
        /*$result = self::getInstance()->database->fetchAll("select buyers_channels.buyer_id as buyerID,users_company_buyer.systemName as buyerTitle,buyers_channels.id as postingID, buyers_channels.title as postingTitle 
                  from buyers_channels 
                  left join users_company_buyer on (buyers_channels.buyer_id = users_company_buyer.id) 
                  where product='payday' order by buyer_id"); */
        $buyers = array();
        $buyers = self::getInstance()->database->fetchAll("select buyers_channels.buyer_id as buyerID,users_company_buyer.systemName as buyerTitle
                  from buyers_channels 
                  left join users_company_buyer on (buyers_channels.buyer_id = users_company_buyer.id) 
                  where product in ('" . implode("','", $product) . "') group by buyer_id order by users_company_buyer.systemName");
        foreach ($buyers AS $bk=>$bv) {
            $ar = self::getInstance()->database->fetchAll("select buyers_channels.id as postingID, buyers_channels.title as postingTitle, buyers_channels.minConstPrice as minConstPrice, buyers_channels.status as status 
                  from buyers_channels  where (buyers_channels.product in ('" . implode("','", $product) . "') AND buyers_channels.buyer_id = ".(int)$bv['buyerID']." ) order by buyers_channels.id");
            
            if(count($ar)){
                foreach($ar as &$el){
                    $el['buyerTitle'] = $bv['buyerTitle']; 
                }    
            }
            
            
            $buyers[$bk]['postings'] = $ar;
        }       
        return $buyers;       
    }
    
    
    public static function getBuyersAndChannels($product){
        /*$result = self::getInstance()->database->fetchAll("select buyers_channels.buyer_id as buyerID,users_company_buyer.systemName as buyerTitle,buyers_channels.id as postingID, buyers_channels.title as postingTitle 
				  from buyers_channels 
				  left join users_company_buyer on (buyers_channels.buyer_id = users_company_buyer.id) 
				  where product='payday' order by buyer_id"); */
        $buyers = array();
        $buyers = self::getInstance()->database->fetchAll("select buyers_channels.buyer_id as buyerID,users_company_buyer.systemName as buyerTitle
				  from buyers_channels 
				  left join users_company_buyer on (buyers_channels.buyer_id = users_company_buyer.id) 
				  where product='{$product}' group by buyer_id order by users_company_buyer.systemName");
        foreach ($buyers AS $bk=>$bv) {
            $ar = self::getInstance()->database->fetchAll("select buyers_channels.id as postingID, buyers_channels.title as postingTitle, buyers_channels.minConstPrice as minConstPrice, buyers_channels.status as status 
                  from buyers_channels  where (buyers_channels.product='{$product}' AND buyers_channels.buyer_id = ".(int)$bv['buyerID']." ) order by buyers_channels.id");
            
            if(count($ar)){
                foreach($ar as &$el){
                    $el['buyerTitle'] = $bv['buyerTitle']; 
                }    
            }
            
            
            $buyers[$bk]['postings'] = $ar;
        }       
        return $buyers;       
    }

    public static function getBuyersProducts($buyerId){

      $data = self::getInstance()->database->fetchAll("
        SELECT DISTINCT buyers_channels.product, leads_type.title
        FROM buyers_channels
        LEFT JOIN leads_type ON buyers_channels.product = leads_type.name
        WHERE buyers_channels.buyer_id = ?
        ORDER BY leads_type.prioritet DESC
      ", array($buyerId));

      $result = array();

      foreach($data as $v){
        $result[$v['product']] = $v['title'];
      }

      return $result;

    }
    
    static public function getBuyerName($id){
        $id = (int)$id;
        if(!isset(self::$namesCache[$id])) self::loadNames(array($id));
        
        return self::$namesCache[$id];   
    }
    
    static public function loadNames(array $array){
        if(count($array)){
            $ids = array();
            foreach($array as $el){
                if(is_numeric($el) && !isset(self::$namesCache[$el])){
                    $ids[] = (int)$el;
                }      
            } 
            
            if(count($ids)){
                $result = T3Db::api()->fetchPairs("select id,systemName from users_company_buyer where id in (" . implode(",", $ids) . ")");               
                
                foreach($ids as $id){
                    if(isset($result[$id])){
                        self::$namesCache[$id] = $result[$id];     
                    }
                    else {
                        self::$namesCache[$id] = "Unknown";   
                    }
                       
                }
                
            }
        }     
    }
    
    /**
    * Создание объекта, который в дальнейшем можно отрендерить на странице
    * 
    * @param mixed $name
    * @return Zend_Form_Element_Select
    */
    static public function renderSelectObject($name = 'buyer_id', $firstOption = null){
        $select = new Zend_Form_Element_Select($name);
        if($firstOption)$select->addMultiOption('', $firstOption);
        $select->addMultiOptions(self::getBuyers_MiniArray());
        $select->setDecorators(array('ViewHelper'));
        return $select;
    }
    
    static protected $buyersMini;
    /**
    * Получение ассотиативного масива продуктов name => title
    * 
    * @param mixed $type
    * @param mixed $lazy
    */
    static public function getBuyers_MiniArray(){
        if(!isset(self::$buyersMini)){ 
            self::$buyersMini = T3Db::api()->fetchPairs("select id,systemName from users_company_buyer order by systemName");
        }
        return self::$buyersMini;
    } 
    
    static public function getBuyerProductAgents()
    {
	
    	if(NULL == self::$buyers_Agents) {
    		$buyers_Agents_tmp = T3UserBuyerAgents::getAgentsList();
    		foreach ($buyers_Agents_tmp AS $batmp) $buyers_Agents[$batmp['AgentID']] = $batmp['AgentNickname'];
    		self::$buyers_Agents = $buyers_Agents;
    	}
    	
    	if(NULL == self::$BuyerProductAgents) {
    		$buyer_channels_agents_array = T3Db::api()->fetchAll("SELECT * FROM buyer_channels_agents");
    		
    		$matrix_data = array();
    		foreach ($buyer_channels_agents_array AS $dt) {
    			$matrix_data[$dt['product']][$dt['buyer_id']] = $dt['agent_id'];
    		}
    		   		
    		self::$BuyerProductAgents = $matrix_data;
    		
    	}
    	
    	    	
    	
    	//varDump2(self::$BuyerProductAgents);
    	// return matrix of product and buyers with agents
    	return self::$BuyerProductAgents;
    }
    
    
    static public function renderBuyerProductAgent($product, $buyer_id)
    {
    	    	
    	$matrix = self::getBuyerProductAgents();
    	if(isset($matrix[$product][$buyer_id])){
    		$dt = $matrix[$product][$buyer_id];
    		return self::$buyers_Agents[$dt];
    	}
    	
    	return '-';
    }
    
    
}



