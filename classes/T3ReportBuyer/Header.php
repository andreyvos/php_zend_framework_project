<?php

class T3ReportBuyer_Header{
    protected $params = array();

    static public $param_buyerId = 'buyer_id';
    static public $param_buyerAgent = 'buyer_agent';

    static public $param_dateFrom       = 'start_date';
    static public $param_dateTill       = 'end_date';
    
    static public $param_product        = 'lead_product';
    static public $param_state        = 'lead_state';

    static public $param_channelType    = 'channelType'; 
    
    static public $param_agentID        = 'agentID'; 
    static public $param_webmasterID    = 'lead_webmaster_id'; 
    static public $param_channelID      = 'channelID';

    public static $paramStartTime = 'start_time';
    public static $paramEndTime = 'end_time';

    public static $paramPageSize = 'page_size';

    public $returnsComments = array();

    public static $pageSizes = array(
      '50' => '50',
      '150' => '150',
      '500' => '500',
      '1000' => '1000',    
    );


    static public $leadStatuses = array(
      "" => "- All -",
      "Sold" => "Sold",
    );
    
    public $title = 'T3Leads Report';

    public $showGetCSV = true;
    
    public $dateFrom_ts;
    public $dateTill_ts;
    public $dateFrom;
    public $dateTill;
    public $dateFrom_Str;
    public $dateTill_Str;
    public $startTime;
    public $endTime;
    
    public $product;
    //public $channelType;

    public $leadStatus;

    public $state;
    
    //public $agentID;
    //public $agentWithout = false;
    public $webmasterID;
    public $buyerId;
	 public $buyerAgentID;
    
    public $leadWebmasterAgentId;

    //public $channelID;
    public $buyerChannelId;
    public $pageSize;
    
    public $addValues = array();
    public $addValuesNotForm = array();
    public $showTtlDisplayOption = false;
    
    
    public $actions = array(
        'summary-days'  =>  'Summary',
        'detailed-info'  =>  'Details',
        'returns' => 'Returns',
        'returns-summary' => 'Returns Summary',
       /* 'leads'         =>  'Leads',
        'traffic'       =>  'Traffic', 
        'returns'       =>  'Returns',
        'bonuses'       =>  'Bonuses',*/
    );
    public $currentAction = '';
    
    
    public function __construct(array $params = array(), $readValues = true){

        T3ReportBuyer_Header::$leadStatuses = array("" => "- All -");
        foreach(T3BuyersStats::$postResultStatuses as $k => $v){
          T3ReportBuyer_Header::$leadStatuses[$k] = $v['title'];
        }

        $this->params = $params;     
        
        if($readValues){
            $this->readValues();
        }  
    }
    
    
    public function readValues(){
        $this->checkTime();
        $this->checkDates(); 
        $this->checkProduct();
        $this->checkState();
        //$this->checkChannelType();
        //$this->checkAgent();
        $this->checkWM();
        $this->checkBuyer();
        //$this->checkWMChannel();
        $this->checkBuyerChannel();
        $this->checkLeadStatus();
        $this->checkLeadWebmasterAgentId();
        $this->checkPageSize();
        $this->checkBuyerAgent();
    }

    public function checkPageSize(){

     $this->pageSize = 
       isset($this->params[T3ReportBuyer_Header::$paramPageSize]) &&
       isset(T3ReportBuyer_Header::$pageSizes[$this->params[T3ReportBuyer_Header::$paramPageSize]]) ?
       $this->params[T3ReportBuyer_Header::$paramPageSize] :
       50;

    }

    public function checkTime(){

      $this->startTime = isset($this->params[T3ReportBuyer_Header::$paramStartTime])?$this->params[T3ReportBuyer_Header::$paramStartTime]:'00:00';
      $this->endTime = isset($this->params[T3ReportBuyer_Header::$paramEndTime])?$this->params[T3ReportBuyer_Header::$paramEndTime]:'23:59';
      
    }

    public function checkLeadWebmasterAgentId(){
      $this->leadWebmasterAgentId = isset($this->params['lead_webmaster_agent_id'])?$this->params['lead_webmaster_agent_id']:null;
    }

    public function checkLeadStatus(){
      $this->leadStatus = isset($this->params['post_result_status'])?$this->params['post_result_status']:null;
    }

    public function checkBuyer(){
      if(T3Users::getCUser()->isRoleBuyer()){
        $this->buyerId = T3Users::getCUser()->company_id;
      }else
        $this->buyerId = isset($this->params[self::$param_buyerId])?$this->params[self::$param_buyerId]:null;
    }
    
    static protected $todayTS;
    static public function getTodayTS(){
        if(is_null(self::$todayTS)){
            self::$todayTS = mktime(0,0,0, date('m'), date('d'), date("Y"));
        }
        return self::$todayTS;    
    }
    
    /**
    * Проверка дат
    */
    protected function checkDates(){
        $error = true;

        if(isset($this->params[self::$param_dateFrom]) && isset($this->params[self::$param_dateTill])){
            $ts1 = strtotime($this->params[self::$param_dateFrom]);
            $ts2 = strtotime($this->params[self::$param_dateTill]);

            if($ts1 > 0 && $ts2 > 0){
                $nowTS = self::getTodayTS();
                $nowTS_1Year = $nowTS - 31536000;
                
                if($ts1 > $nowTS)$ts1 = $nowTS;
                if($ts2 > $nowTS)$ts2 = $nowTS;
                
                if($ts1 < $nowTS_1Year)$ts1 = $nowTS_1Year;
                if($ts2 < $nowTS_1Year)$ts2 = $nowTS_1Year;
                
                $error = false;
                $this->dateFrom_ts = min($ts1, $ts2);
                $this->dateTill_ts = max($ts1, $ts2);
 
            }    
        }

        if($error){
            $this->dateFrom_ts = mktime(0, 0, 0, date("m"), date("d")-7, date("Y"));
            $this->dateTill_ts = mktime(0, 0, 0, date("m"), date("d"),   date("Y"));    
        }
        
        $this->dateFrom     = date("Y-m-d",  $this->dateFrom_ts);
        $this->dateFrom_Str = date("M d, Y", $this->dateFrom_ts);
        
        $this->dateTill     = date("Y-m-d",  $this->dateTill_ts);
        $this->dateTill_Str = date("M d, Y", $this->dateTill_ts);

    }
    
    /**
    * Проверка продукта
    */
    protected function checkProduct(){
        $this->product = '';
        if(isset($this->params[self::$param_product]) && T3Products::isProduct($this->params[self::$param_product])){
            $this->product = $this->params[self::$param_product];        
        }
    }

    protected function checkState(){
      $this->state = isset($this->params[self::$param_state]) ? $this->params[self::$param_state] : null;
    }
    
    /**
    * Проверка типа канала
    */
   /* protected function checkChannelType(){
        $this->channelType = null;
        if(isset($this->params[self::$param_channelType]) && in_array($this->params[self::$param_channelType], array('js_form', 'post_channel'))){
            $this->channelType = $this->params[self::$param_channelType];    
        }
    }*/
    
    /**
    * Проверка агента
    */
    /*protected function checkAgent(){
        $this->agentID = null;
        $this->agentID_str = '';
        
        $agn = ifset($this->params[self::$param_agentID]); 
        
        $currentUser =& T3Users::getInstance()->getCurrentUser();
        
        if($currentUser){
            if($currentUser->isRoleAdmin()){
                if($agn == '-'){
                    $this->agentWithout = true;  
                    $this->agentID_str = "-";  
                }
                else if($agn && T3UserWebmasterAgents::isAgent($agn)) {
                    $this->agentID = (int)$agn; 
                    $this->agentID_str = $this->agentID;   
                }          
            }
            else if($currentUser->isRoleWebmasterAgent()){
                $this->agentID = $currentUser->company_id;
                $this->agentID_str = $this->agentID;           
            }
        }
    }*/
    
    /**
    * Проверка вебмастера
    */
    protected function checkWM(){
        $this->webmasterID = null;
        
        $wm = (int)ifset($this->params[self::$param_webmasterID]);
        
        $currentUser =& T3Users::getInstance()->getCurrentUser();
        
        if($currentUser){
            if($currentUser->isRoleAdmin() && $wm && T3WebmasterCompanys::isWebmaster($wm)){
                $this->webmasterID = $wm;         
            }
            else if($currentUser->isRoleWebmasterAgent() && $wm && T3WebmasterCompanys::isWebmaster($wm, $this->agentID)){
                $this->webmasterID = $wm;        
            }
            else if($currentUser->isRoleWebmaster()){
                $this->webmasterID = $currentUser->company_id;        
            }
        }    
    }
    
    /**
    * Проверка канала вебмастера
    */
   /* protected function checkWMChannel(){
        $this->channelID = null;
    }*/

    protected function checkBuyerChannel(){
       // $this->channelID = null;
       $this->buyerChannelId = isset($this->params['buyer_channel_id'])?$this->params['buyer_channel_id']:null;
    }
    
    public function render(){

        MyZend_Site::addJS('BuyersSelectHeader.js');

        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "Header");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_"); 
        
        $view->class = $this;

        $view->buyers = array('' => ' -- All -- ') + T3BuyersStats::getInstance()->getBuyersList(T3Users::getCUser()->isRoleBuyerAgent());
        
        if(empty($this->buyerId)){
          $this->buyersChannels = array('' => ' -- All -- ');
        }else{
          $this->buyersChannels = array('' => ' -- All -- ') + T3BuyersStats::getInstance()->getBuyerChannels($this->buyerId, T3Users::getCUser()->isRoleBuyerAgent());
        }

        $this->webmastersAgents = array('' => ' -- All -- ') + T3BuyersStats::getInstance()->getWebmastersAgents();

        $view->showGetCSV = $this->showGetCSV;


        if(T3Users::getCUser()->isRoleBuyer()){
          $availableProducts = T3Buyers::getBuyersProducts(T3Users::getCUser()->company_id);
        }else{
          $availableProducts = T3Products::getProducts_MiniArray();
          if(T3Users::getCUser()->isRoleBuyerAgent()){

            $productsForAgent = T3UserBuyerAgents::getProducts();
            $allProducts = $availableProducts;
            $availableProducts = array();
            foreach($productsForAgent as $v){
              $availableProducts[$v] = $allProducts[$v];
            }
          }
        }

        $view->products = array('' => ' -- All -- ') + $availableProducts;

        if(T3Users::getCUser()->isRoleBuyerAgent()){
          $view->productsBuyers = T3BuyersStats::getInstance()->getBuyersProductsDistribution(T3UserBuyerAgents::getProducts());
        }else{
          $view->productsBuyers = T3BuyersStats::getInstance()->getBuyersProductsDistribution(array_keys(T3Products::getProducts()));
        }

        $view->useDynamicBuyersList = true;
        foreach($view->productsBuyers as $product => &$v)
          $v = array('' => ' -- All -- ') + $v;


        if($this->currentAction == 'returns-summary'){
          $view->returnsComments = $this->returnsComments;
        }

        if(T3Users::getCUser()->isRoleAdmin()){
        	$view->all_buyer_agents = T3UserBuyerAgents::getAgentsListPairs();
        }
        
        
        return $view->render("main.phtml");

    } 
    
    
    public function getURL_Main($typeMacros = true, array $paramsManual = array()){
        if($typeMacros) $type = '{type}';
        else            $type = $this->currentAction;
        
        $getParams_Array = array(
            'start_date'    => urlencode($this->dateFrom_Str),
            'end_date'      => urlencode($this->dateTill_Str),
            'product'       => urlencode($this->product),

//            'channelType'   => urlencode($this->channelType),
            //'agentID'       => urlencode($this->agentID),
            'webmasterID'   => urlencode($this->webmasterID),    
        );
        
        $getParams_Array = $getParams_Array + $this->addValues; 
        
        if(count($paramsManual)){
            foreach($paramsManual as $key => $val){
                $getParams_Array[$key] = $val;    
            }    
        }
        
        $getParams = array();
        foreach($getParams_Array as $key => $val){
            $getParams[] = "{$key}={$val}";         
        }
        
        return "/en/account/report/{$type}?" . implode("&", $getParams);
    }
    
    public function getURL_Dates($typeMacros = true, array $paramsManual = array()){
        $paramsManual['start_date'] = "{dateFrom}";
        $paramsManual['end_date']   = "{dateTill}";
        
        return $this->getURL_Main($typeMacros, $paramsManual);  
    }
    
    public function addValue($key, $value, $addForm = true){
        $this->addValues[$key] = $value; 
        unset($this->addValuesNotForm[$key]);
        if($addForm == false){
            $this->addValuesNotForm[$key] = true;    
        }    
    }
     
    public function getParams()
    {
        return $this->params;
    }
    
    
    public function checkBuyerAgent()
    {
    	$this->buyerAgentID = isset($this->params['buyer_agent'])?$this->params['buyer_agent']:'';
    }
    
}



