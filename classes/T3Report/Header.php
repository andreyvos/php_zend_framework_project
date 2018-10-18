<?php

class T3Report_Header{
    protected $params = array();

    static public $param_dateFrom       = 'start_date';
    static public $param_dateTill       = 'end_date';

    static public $param_product        = 'product';
    static public $param_channelType    = 'channelType';

    static public $param_agentID        = 'agentID';
    static public $param_webmasterID    = 'webmasterID';
    static public $param_channelID      = 'channelID';

    public $title = 'T3Leads Report';


    public $dateFrom_ts;
    public $dateTill_ts;
    public $dateFrom;
    public $dateTill;
    public $dateFrom_Str;
    public $dateTill_Str;

    public $product;
    public $channelType;


    public $agentID;
    public $agentWithout = false;
    public $webmasterID;
    public $channelID;
    public $channelObject;

    public $webmasterType;
    public $webmasters = array();
    public $webmasterAction; //

    public $addValues = array();
    public $addValuesNotForm = array();


    public function webmastersV1(){
        if(count($this->webmasters)){
            return T3Db::v1()->fetchCol("select id from `user` where t3v2ID in ('" . implode("','", $this->webmasters) . "')");
        }
        else {
            return array();
        }
    }

    public $actions = array(
        'summary-days'      =>  'Summary',
        'leads'             =>  'Leads',
        'traffic'           =>  'Traffic',
        'returns'           =>  'Returns',
        'bonuses'           =>  'Bonuses',
        'backend'           =>  'Backend',
        'backend-details'   =>  'Backend Clients',
        'backend-solds'     =>  'Backend Solds',
    );
    public $currentAction = '';


    public function getActions(){
        if(T3Users::getCUser()->isRoleWebmasterAgent()){
            unset($this->actions['bonuses'], $this->actions['backend-solds']);
        }
        if(T3Users::getCUser()->isRoleWebmaster()){
            unset($this->actions['bonuses'], $this->actions['backend'], $this->actions['backend-details'], $this->actions['backend-solds']);
        }
        else if(T3Users::getCUser()->isRoleBuyerAgent()){
            unset($this->actions['returns'], $this->actions['bonuses'], $this->actions['backend-details'], $this->actions['backend-solds']);
        }

        return $this->actions;
    }

    public function __construct(array $params = array(), $readValues = true){
        $this->params = $params;

        $this->channelObject = new AutoComplite_WebmasterChannel("channel", array(
            'product'   => 'product',
            'type'      => 'channelType',
            'webmaster' => 'wmList_rep_dataOne',
        ));
        $this->channelObject->setWidth('100%');

        if($readValues){
            $this->readValues();
        }
    }


    public function readValues(){
        $this->checkDates();
        $this->checkProduct();
        $this->checkChannelType();
        $this->checkAgent();
        $this->checkWM();
        $this->checkWMChannel();
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
                $nowTS_10Year = $nowTS - 10 * 31536000;

                if($ts1 > $nowTS)$ts1 = $nowTS;
                if($ts2 > $nowTS)$ts2 = $nowTS;

                if($ts1 < $nowTS_10Year)$ts1 = $nowTS_10Year;
                if($ts2 < $nowTS_10Year)$ts2 = $nowTS_10Year;

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
    protected function checkProduct(){
        $this->product = null;

        if(isset($this->params[self::$param_product]) && T3Products::isProduct($this->params[self::$param_product])){
            $this->product = $this->params[self::$param_product];
        }
    }

    /**
    * Проверка типа канала
    */
    protected function checkChannelType(){
        $this->channelType = null;
        if(isset($this->params[self::$param_channelType]) && in_array($this->params[self::$param_channelType], array('js_form', 'post_channel', 'form_js', 'form_mobile'))){
            $this->channelType = $this->params[self::$param_channelType];
        }
    }

    /**
    * Проверка агента
    */
    protected function checkAgent(){
        $currentUser = T3Users::getInstance()->getCurrentUser();

        $this->agentID = null;
        $this->agentID_str = '';
        $defaultAgent = (T3Users_AgentManagers::isPubManager()) ? T3Users::getCUser()->id : null;

        $agn = ifset($this->params[self::$param_agentID], $defaultAgent);

        if($currentUser){
            if($currentUser->isRoleAdmin() || $currentUser->isRoleBuyerAgent() || T3Users_AgentManagers::isPubManager()){
                if($agn == '-'){
                    $this->agentWithout = true;
                    $this->agentID_str = "-";
                }
                else if($agn && T3UserWebmasterAgents::isAgent($agn)) {
                    $this->agentID = (int)$agn;
                    $this->agentID_str = $this->agentID;
                }
            }
            else if(T3Users_AgentManagers::isWebmasterAgentManager()){
                if(in_array($agn, T3UserWebmasterAgents::getAgent( T3Users::getCUser()->id )->getSlaveAgents()) && T3UserWebmasterAgents::isAgent($agn)){
                    $this->agentID = (int)$agn;
                    $this->agentID_str = $this->agentID;
                }
                else {
                    $this->agentID = $currentUser->id;
                    $this->agentID_str = $this->agentID;
                }
            }
            else if($currentUser->isRoleWebmasterAgent()){
                $this->agentID = $currentUser->id;
                $this->agentID_str = $this->agentID;
            }
        }
    }

    /**
    * Проверка вебмастера
    */
    protected function checkWM(){
        $this->webmasterID = null;

        T3Ui_WebmasterList::getInstance('rep')->run();
        $this->webmasterType    = T3Ui_WebmasterList::getInstance('rep')->type;
        $this->webmasterAction  = T3Ui_WebmasterList::getInstance('rep')->action;
        $this->webmasters       = T3Ui_WebmasterList::getInstance('rep')->getWebmasters();

        // $wm = (int)ifset($this->params[self::$param_webmasterID]);
        $wm = 0;
        if(isset($this->webmasters[0])){
            $wm =  $this->webmasters[0];
        }

        $currentUser = T3Users::getInstance()->getCurrentUser();

        if($currentUser){

            //varExport(T3Users_AgentManagers::isWebmasterAgentManager());
            //varExport($wm);
            if(
                ($currentUser->isRoleAdmin() || $currentUser->isRoleBuyerAgent() || T3Users_AgentManagers::isPubManager() || T3Users_AgentManagers::isWebmasterAgentManager()) &&
                $wm &&
                T3WebmasterCompanys::isWebmaster($wm)
            ){
                $this->webmasterID = $wm;

                //echo "!{$this->webmasterID}!";
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
    protected function checkWMChannel(){
        /*
        $this->channelID = null;

        $cID = ifset($this->params[self::$param_channelID]);
        if(strlen($cID)){
            $cID = IdEncryptor::decode($cID);
            if(T3Channels::isAccess($cID)){
                $this->channelID = $cID;
            }
        }
        */
        $this->channelID = $this->channelObject->getValue();
    }

    public function render($showPeriodType = false){
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "Header");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_");

        $view->class = $this;
        $view->showPeriodType = $showPeriodType;

        return $view->render("main.phtml");
    }


    public function getURL_Main($typeMacros = true, array $paramsManual = array()){
        if($typeMacros) $type = '{type}';
        else            $type = $this->currentAction;

        $getParams_Array = array(
            'start_date'    => urlencode($this->dateFrom_Str),
            'end_date'      => urlencode($this->dateTill_Str),
            'product'       => urlencode($this->product),
            'channelType'   => urlencode($this->channelType),
            'agentID'       => urlencode($this->agentID),
            'webmasterID'   => urlencode($this->webmasterID),
            'channel'       => urlencode($this->channelID),
            'channel_txt'   => urlencode(ifset($_GET['channel_txt'])),
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

}