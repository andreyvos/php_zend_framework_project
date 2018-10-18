<?php

TableDescription::addTable('users_company_webmaster', array(
  'id',                       //  int(1) unsigned
  'systemName',               //  varchar(255)
  'companyName',              //  varchar(255)
  'points',                   //  int(1) unsigned
  'payPeriod',                //  enum('w','b','s','m')
  'refaffid',                 //  int(1) unsigned
  'autoSubAccounts',          //  enum('0','1')
  'regProductsInterest',      //  text
  'regComments',              //  text
  'defaultProcCallVerify',    //  int(1) unsigned
  'agentID',                  //  int(1) unsigned
  'status',                   //  enum('lock','hold','noappr','activ')
  'balance',                  //  decimal(12,2)
  'reg_date',                 //  datetime 
  'reg_website',
  'howDidFindUs',
  'HTTP_REFERER',
  'first_referer',
  'gotInTouchWithAM',
  'T3LeadsVersion1_ID',
  'T3LeadsVersion1_Login',
  'taxpayer_form_return_url',
  'fw9_form_filled',
  'fw9_form_post',
  'fw9_docu_sign_envelope_id',
  'fw8eci_form_filled',
  'fw8eci_form_post',
  'fw8eci_docu_sign_envelope_id',
));

TableDescription::$tables['users_company_webmaster']->addSaveException('balance');


class T3WebmasterCompany extends DbSerializable implements T3CompanyInterface {

    public $id;
    public $systemName;
    public $companyName;
    public $points = 0;
    public $payPeriod = 'twicemonthly';
    public $refaffid = 0;
    public $autoSubAccounts = 1;
    public $regProductsInterest;
    public $regComments;
    public $defaultProcCallVerify = 50;
    public $agentID = 0;
    public $status = 'noappr';
    public $balance = 0;
    public $reg_date;
    public $reg_website;
    public $howDidFindUs;
    public $HTTP_REFERER;
    public $first_referer = 0;
    public $gotInTouchWithAM;
    public $T3LeadsVersion1_ID = '0'; 
    public $T3LeadsVersion1_Login;
    public $taxpayer_form_return_url;
    public $fw9_form_filled = 0;
    public $fw9_form_post;
    public $fw9_docu_sign_envelope_id;
    public $fw8eci_form_filled = 0;
    public $fw8eci_form_post;
    public $fw8eci_docu_sign_envelope_id;
    

    public function getNextPayment(){
      return T3Payments::getInstance()->getNextPaymentPayDate($this);
    }


    public function  __construct() {
        parent::__construct();
        $this->tables = array('users_company_webmaster');
        $this->readNewIdAfterInserting = 'id'; // При Insert получать новый ID из MySQL переменной id (select @id)
    }
    
    /**
    * @return T3UserWebmasterAgent
    */
    public function getCompanyAgent(){
        if($this->agentID){
            $agent = new T3UserWebmasterAgent();
            $agent->fromDatabase($this->agentID);
            if($agent->id){
                return $agent;    
            }
        }
        return null;    
    }
    
    public function getSiteManagementLink(){
        return "/en/account/webmasters/main/id/{$this->id}";    
    }

    /**
    * Создание новой компании
    * 
    * @param mixed $companyName
    * @param mixed $refCompanyID
    * @param mixed $agentID
    * @param mixed $params
    * @return T3WebmasterCompany
    */
    static public function createNewCompany($companyName, $systemName, $refCompanyID = null, $agentID = null, $params = array(), $group = 'english'){
        $obj = new T3WebmasterCompany();
        
        $obj->setParams($params);
        $obj->HTTP_REFERER = $params['HTTP_REFERER'];
        $obj->first_referer = T3FirstReferer::getID();
        $obj->gotInTouchWithAM = $params['gotInTouchWithAM'];
        $obj->companyName   =   $companyName;
        $obj->systemName    =   $systemName;
        $obj->refaffid      =   $refCompanyID;
        
        $obj->reg_date      =   date("Y-m-d H:i:s");
        
        if ($group == 'russian') {
            $obj->regComments = "t3leads.ru";
        }

        $obj->insertIntoDatabase();
        
        T3FirstReferer::addWebmaster($obj->id);

        if($agentID === false){
            $obj->changeAgent("0", "When you create a web master, the agent is disabled");
        }
        else if($agentID > 0 && $params['gotInTouchWithAM'] == 1){
            $obj->changeAgent($agentID, "Webmaster select an agent in the registration form");
        }
        else {
            $obj->changeAgent(T3UserWebmasterAgents::getRandomAgent($group), "Selected a random agent");
        }

        
                
        return $obj;
    }

    //public function get
    
    /**
    * Роль для пользователя из этой компании
    */
    static public function getConst_UserRole(){
        return 'webmaster';    
    }
    
    static public function getSubaccountID($companyID, $subAccountName){
        $id = T3System::getConnect()->fetchOne("select id from users_company_webmaster_subacc where idcompany=? and `name`=? and del=?", array($companyID, $subAccountName, '0')); 
        
        if(!$id){
            T3System::getConnect()->insert('users_company_webmaster_subacc',array(
                'idcompany'     =>  $companyID,
                'name'          =>  $subAccountName,
                'title'         =>  $subAccountName,
                'del'           =>  '0',
                'create_date'   =>  new Zend_Db_Expr('NOW()'),
            ));   
            
            $id = T3System::getConnect()->lastInsertId();    
        } 
        
        return $id;   
    }
    
    public function updateBalance($balance){
        if($balance != 0){
            $balance = round((float)$balance, 2);
            
            $this->database->update('users_company_webmaster', array(
                'balance' => new Zend_Db_Expr("balance+{$balance}")    
            ), "id='" . ($this->id+0) . "'");
            
            /*
            $this->database->query("
            update 
                users_company_webmaster_balances 
            set 
                approve=1 
            where 
                webmaster='" . ($this->id+0) . "' and 
                `value`='{$balance}' and 
                `approve` = 0 
            order by id desc
            limit 1
            ");         
            */
            
            $this->balance += $balance; 
        }    
    } 

    public function getCompanyEmail($company_id = null)
    {
        if(NULL === $company_id) {
            $company_id = (int)$this->id;
        } else {
            $company_id = (int)$company_id;
        }
        
        if($company_id) {
            return $this->database->fetchRow('SELECT email, nickname FROM users WHERE company_id = '.$company_id.' ORDER BY id ASC LIMIT 1  ');
        }
        
        return '';
    }
    
    
    public function getTotalAmount(){
        return round($this->balance + $this->getPaidAmount(), 2);         
    }
    
    public function getPaidAmount(){
        return round(T3Db::api()->fetchOne("select sum(`value`) from webmasters_payments_pays where webmaster_id=?", $this->id), 2);    
    }
    
    public function getAccountManagerByUserId($id){
        return T3Cache_User::get(T3Cache_WebmasterAgent::get($id), false);
    }

    public function changeAgent($agentID, $reason = ''){
        if($this->id){
            $this->agentID = (int)$agentID;

            T3Db::api()->update("users_company_webmaster", array(
                'agentID' => $this->agentID,
            ), "id='{$this->id}'");

            T3Db::api()->insert("webmasters_agents_history", array(
                'webmaster'      => $this->id,
                'agent'          => $this->agentID,
                'change_date'    => date("Y-m-d H:i:s"),
                'admin_user_id'  => T3Users::getInstance()->getCurrentUserId(),
                'comment'        => $reason,
            ));
        }
    }
    
    public function userStatusLog($data)
    {    	
    	$data['date_change'] = date("Y-m-d H:i:s");
    	T3Db::api()->insert("users_status_log", $data);
    }
    
    public function userStatusLogItems($webmasterID = null, $type = null)
    {
    	if(NULL === $webmasterID) $webmasterID = $this->id;
    	
    	if(NULL == $type) return T3Db::api()->fetchAll("SELECT * FROM users_status_log WHERE user_id = ".$webmasterID." ORDER BY id DESC");
    	$type = (int)$type;
    	return T3Db::api()->fetchAll("SELECT * FROM users_status_log WHERE user_id = ".$webmasterID." AND user_type = ".$type."  ORDER BY id DESC ");
    }


    public function setFw9Filled(){

      $this->fw9_form_filled = 1;
      $this->saveToDatabase();

      header('Location: ' . $this->taxpayer_form_return_url);
      
    }

    public function setFw8eciFilled(){

      $this->fw8eci_form_filled = 1;
      $this->saveToDatabase();

      header('Location: ' . $this->taxpayer_form_return_url);

    }

    public function fw9IsAppropriateForm(){
      $user = new T3User();
      $user->fromDatabase(array('company_id' => $this->id));
      return $user->country == 'US' || $user->country == 'USA' || $user->country == 'United States';
    }

    public function fw8eciIsAppropriateForm(){
      $user = new T3User();
      $user->fromDatabase(array('company_id' => $this->id));
      return !($user->country == 'US' || $user->country == 'USA' || $user->country == 'United States');
    }

    public function fw9OrFw8eciRequired(){
return;
      if(
        ($this->fw9IsAppropriateForm() && $this->fw9_form_filled)
        || ($this->fw8eciIsAppropriateForm() && $this->fw8eci_form_filled)
      )
        return;

      $this->taxpayer_form_return_url = $_SERVER['REQUEST_URI'];
      $this->saveToDatabase();

      if($this->fw9IsAppropriateForm()){
        header('Location: /en/account/webmasters/formfw9/');
      }else{
        header('Location: /en/account/webmasters/formfw8eci/');
      }

      return;

    }
    
}

