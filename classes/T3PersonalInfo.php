<?php



TableDescription::addTable('users_personal_info', array(
                               'iduser',
                               'phone'
                           ));


/*
class T3PersonalInfo extends DbSerializable{

  public $id;
  public $company_name;
  public $status;
  public $points;
  public $pay_period;
  public $pay_info;
  public $pay_mininmal;
  public $pay_accept;
  public $seller;
  public $buyer;
  public $refaffid;
  public $auto_subacc;
  public $seller_agent_id;
  public $buyer_agent_id;
  public $products_interest;
  public $comments;
  public $secure_id;
  public $balance;
  public $reg_date;
  public $defaultProcCallVerify;
  public static $_instance;

  protected $usersDistribution = array();


  protected function initialize() {


   if(!isset($this->className))$this->className = __CLASS__;

    parent::__construct();
    $this->tables = array('users_company');

    $this->system = T3System::getInstance();
    $this->database = $this->system->getConnect();

    $keys = array(
      'id', 'email'
    );

    foreach($keys as $v)
      $this->usersDistribution[$v] = array();

  }


  public static function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self();
      self::$_instance->initialize();
    }
    return self::$_instance;
  }



  public function getCompanyById($id){
    return $this->getCompany($id);
  }



  protected function getCompany($field, $value = null){
    if(is_null($value)){
      $value = $field;
      $field = 'id';
    }
    if(!isset($this->usersDistribution[$field][$value])){
      $object = T3UserCompany::createFromDatabase(array($field => $value));
      if($object===false)
        return false;
      $this->insertUserIntoArray($object);
    }
    return $this->usersDistribution[$field][$value];
  }



  protected function insertUserIntoArray($object){
    foreach($this->usersDistribution as $k => $v)
      if(isset($object->$k))
        $this->usersDistribution[$k][$object->$k] = $object;
    $this->users[] = $object;
  }

}
*/


throw new Exception('Not Implemented');

class T3PersonalInfo extends DbSerializable {

    public $iduser;
    public $phone;

    public function  __construct($loadCompanyID = null) {
        
        if (!isset($this->className))$this->className = __CLASS__;

        parent::__construct();
        $this->tables = array('users_personal_info');

        if (isset($loadCompanyID) && !is_null($loadCompanyID) && is_numeric($loadCompanyID)) {
            $this->fromDatabase(array('iduser' => $loadCompanyID));
        }
    }

}

