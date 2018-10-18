<?php

class T3RevNet_Header{
    protected $params = array();
    
    static public $param_dateFrom       = 'start_date';
    static public $param_dateTill       = 'end_date';
    
    static public $param_account        = 'account';
    static public $param_product        = 'product';
    
    static public $param_webmasterID    = 'webmasterID'; 
    
    public $title = 'RevNet Report';
    
    public $dateFrom_ts;
    public $dateTill_ts;
    public $dateFrom;
    public $dateTill;
    public $dateFrom_Str;
    public $dateTill_Str;
    
    public $accounts = array();
    public $account;

    public $products = array();
    public $product;
    
    public $webmasterID;
    
    public $addValues = array();
    public $addValuesNotForm = array();
    
    public $actions = array(
        'summary'  =>  'Summary',
        'details'  =>  'Leads',
        'pings'    =>  'Track Pings',
    );
    public $currentAction = '';
    
    
    public function __construct(array $params = array(), $readValues = true){
        $this->params = $params;     
        
        if($readValues){
            $this->readValues();
        }  
    }
    
    
    public function readValues(){
        $this->checkDates(); 
        $this->checkAccounts();
        $this->checkProducts();
        $this->checkWM();         
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
            $this->dateFrom_ts = mktime(0, 0, 0, date("m"), date("d")-6, date("Y"));
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
    protected function checkAccounts(){
        $this->account = null;
        $this->accounts = T3Db::api()->fetchPairs("select system_name, concat(postSubaccount, ' (', system_name, ')') from revnet_accounts");
        
        if(isset($this->params[self::$param_account]) && isset($this->accounts[$this->params[self::$param_account]])){
            $this->account = $this->params[self::$param_account];        
        } 
    }

    protected function checkProducts(){
        $this->product = null;
        $this->products = T3Db::api()->fetchPairs("SELECT `name`,`title` FROM leads_type WHERE `name` IN ('payday','ukpayday','capayday','personalloan')");

        if(isset($this->params[self::$param_product]) && isset($this->products[$this->params[self::$param_product]])){
            $this->product = $this->params[self::$param_product];
        }
    }
    
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
    
    public function render(){
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "Header");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_"); 
        
        $view->class = $this;
        
        return $view->render("main.phtml");
    } 
    
    
    public function getURL_Main($typeMacros = true, array $paramsManual = array()){
        if(is_string($typeMacros)){
            $type = $typeMacros;       
        }
        else {
            if($typeMacros) $type = '{type}';
            else            $type = $this->currentAction;
        }
        
        $getParams_Array = array(
            'start_date'    => urlencode($this->dateFrom_Str),
            'end_date'      => urlencode($this->dateTill_Str),
            'account'       => urlencode($this->account),
            'product'       => urlencode($this->product),
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
        
        return "/en/account/integrations/revnet/{$type}?" . implode("&", $getParams);
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
     
    public function getParams(){
        return $this->params;
    }
}