<?php

TableDescription::addTable('users_company_buyer', array(
  'id',                       //  int(1) unsigned
  'systemName',               //  varchar(32)
  'companyName',              //  varchar(255)
  'agent_id',                 //  int(1)
  'timezone',                 //  char(3)
  'Country',                  //  varchar(64)
  'State',                    //  varchar(64)
  'City',                     //  varchar(255)
  'ZIP',                      //  varchar(20)
  'Address',                  //  varchar(255)
  'invoices_emails',          //  text
  'invoices_period_type',     //  enum('weekly','biweekly','monthly','days')
  'invoices_period',          //  int(1)
  'invoices_term',            //  int(1)
  'invoices_next_default',    //  datetime
  'invoices_last_num',        //  int(1)
  'tech_emails',
  'agentID',                  //  int(1) unsigned
  'status',                   //  enum('lock','hold','noappr','activ')
  'balance',                  //  decimal(12,2)
  'reg_date',                 //  datetime
  'groupID',                  //  int(11)
  'relevance',
  'return_ping_auth_key',
  'invoice_currency_chosen',
  'invoice_currency',
  'invoice_template_chosen',
  'invoice_template',
  'invoices_are_manual',
  'is_in_collections',  
));



class T3BuyerCompany extends DbSerializable implements T3CompanyInterface {

  public $id;
  public $systemName;
  public $companyName;
  public $agent_id;
  public $timezone;
  public $Country;
  public $State;
  public $City;
  public $ZIP;
  public $Address;
  public $invoices_emails;
  public $invoices_period_type;
  public $invoices_period;
  public $invoices_term;
  public $invoices_next_default;
  public $invoices_last_num;
  public $tech_emails;
  public $agentID;
  public $status;
  public $balance = 0;
  public $reg_date;
  public $groupID=1;
  public $relevance;
  public $return_ping_auth_key;
  public $invoice_currency_chosen = 0;
  public $invoice_currency;
  public $invoice_template_chosen = 0;
  public $invoice_template;
  public $invoices_are_manual = 0;
  public $is_in_collections = 0;




    public function __construct() {
        parent::__construct();
        $this->tables = array('users_company_buyer');
        
        $this->readNewIdAfterInserting = 'id'; // При Insert получать новый ID из MySQL переменной id (select @id)
    }
    
    /**
    * put your comment there...
    * 
    * @param mixed $companyName
    * @param mixed $fullCompanyName
    * @param mixed $agentID
    * @param mixed $params
    * 
    * @return T3BuyerCompany
    */
    static public function createNewCompany($companyName, $fullCompanyName = null, $agentID = null, $params = array()){
        $obj = new T3BuyerCompany();

        $obj->setParams($params);

        
        $obj->systemName    =   $companyName;
        
        if($fullCompanyName == ''){
            $fullCompanyName = $companyName;    
        }
        
        $obj->companyName   =   $fullCompanyName; 

        if($agentID === false)  $obj->agentID = "0";
        else if($agentID > 0)   $obj->agentID = $agentID;
        else                    $obj->agentID = T3UserBuyerAgents::getRandomAgent();

        $obj->reg_date      =   date("Y-m-d H:i:s");

        $obj->return_ping_auth_key = T3BuyerReturnPings::getInstance()->getRandomAuthenticationKeyForBuyer();

        $obj->insertIntoDatabase();

        return $obj;
    }
    
    /**
    * Роль для пользователя из этой компании
    */
    static public function getConst_UserRole(){
        return 'buyer';    
    }
    
    public function updateBalance($balance){
        $this->database->update('users_company_buyer', array(
            'balance' => new Zend_Db_Expr("balance+{$balance}")    
        ), 'id=' . $this->id);
        
        $this->balance += $balance;     
    }
    
    public function getCompanyAgent(){
        if($this->agentID){
            $agent = new T3UserBuyerAgent();
            $agent->fromDatabase($this->agentID);
            if($agent->id){
                return $agent;    
            }
        }
        return null;    
    }
    
    public function getSiteManagementLink(){
        return "/en/account/buyers/main?id={$this->id}";    
    }
    
    public  function getByGroupId($groupId=null){
        if($groupId!=null)
        return $this->database->select()->from('users_company_buyer')->where('groupID=?',(int)$groupId)->query()->fetchAll();
        return $this->database->select()->from('users_company_buyer')->where('groupID=?',(int)$this->groupID)->query()->fetchAll();
    }
    
    public function getTotalAmount(){
        return 0;    
    }
    
    public function getPaidAmount(){
        return 0;    
    }

}


