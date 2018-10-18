<?php


TableDescription::addTable('webmasters_bonuses', array(
  'id',                       //  int(11)
  'webmaster_id',             //  int(11)
  'payment_id',               //  int(11)
  'action_sum',               //  decimal(10,2)
  'action_datetime',          //  datetime
  'lead_id',                  //  int(11)
  'lead_product',             //  varchar(255)
  'lead_channel_id',
  'lead_get_method',
  'from_old_system',          //  tinyint(1)
  'comment',                  //  text
));

class T3Bonus extends DbSerializable {

  public $id;
  public $webmaster_id;
  public $payment_id;
  public $action_sum;
  public $action_datetime;
  public $lead_id;
  public $lead_product;
  public $lead_channel_id;
  public $lead_get_method;
  public $from_old_system = 0;
  public $comment;

  public function __construct() {

    parent::__construct();

    $this->tables = array('webmasters_bonuses');

  }

  public function fillAdditionalData(T3Lead $lead = null){
    if(!empty($this->lead_id)){
      if(empty($lead)){
        $lead = new T3Lead();
        $lead->fromDatabase($this->lead_id);
      }
      $this->lead_channel_id = $lead->channel_id;
      $this->lead_product = $lead->product;
      $this->lead_get_method = $lead->get_method;
    }else{
      $this->lead_channel_id = null;
      $this->lead_product = null;
      $this->lead_get_method = null;
    }
  }

  public function make(){
    // Поиск лида, если он есть и запись парметров по этому лиду
    $this->fillAdditionalData();
    
    T3WebmasterCompanys::getCompany($this->webmaster_id)->updateBalance($this->action_sum);
    
    T3Report_Summary::addNewBonus($this);
    $this->insertIntoDatabase();

  }

}








