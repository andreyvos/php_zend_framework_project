<?php

TableDescription::addTable('buyers_statistics', array(
  'id',                       //  int(11)
  'record_datetime',          //  datetime
  'buyer_id',                 //  int(11)
  'buyer_channel_id',         //  int(11)
  'lead_webmaster_id',        //  int(11)
  'lead_webmaster_agent_id',  //  int(11)
  'lead_id',                  //  int(11)
  'lead_product',             //  varchar(255)
  'lead_status',              //  varchar(255)
  'lead_get_method',          //  varchar(255)
  'lead_is_from_v1',          //  tinyint(1)
  'lead_email',
  'lead_ip',
  'lead_state',
  'lead_ssn',
  'lead_homephone',
  'post_result_id',
  'post_result_status',       //  varchar(255)
  'is_return',                //  tinyint(1)
  'return_id',                //  int(11)
  'earnings',                 //  decimal(10,2)
  'error_description',
));



class T3BuyersStatsItem extends DbSerializable{

  public $id;
  public $record_datetime;
  public $buyer_id;
  public $buyer_channel_id;
  public $lead_webmaster_id;
  public $lead_webmaster_agent_id;
  public $lead_id;
  public $lead_product;
  public $lead_status;
  public $lead_get_method;
  public $lead_is_from_v1;
  public $lead_email;
  public $lead_ip;
  public $lead_state;
  public $lead_ssn;
  public $lead_homephone;
  public $post_result_id;
  public $post_result_status;
  public $is_return;
  public $return_id;
  public $earnings;
  public $error_description;

  public function __construct() {

    parent::__construct();
    $this->tables = array('buyers_statistics');

  }

  public function fromNewLead(T3Lead $lead, T3BuyerChannel_PostResult $postResult, $datetime, $buyerId, $buyerChannelId){

    $this->lead_webmaster_id = $lead->affid;
    $this->lead_webmaster_agent_id = $lead->agentID;
    $this->lead_id = $lead->id;
    $this->lead_product = $lead->product;
    $this->lead_status = $lead->status;
    $this->lead_get_method = $lead->get_method;
    $this->lead_is_from_v1 = 0;
    $this->lead_email = $lead->data_email;
    $this->lead_ip = $lead->ip_address;
    $this->lead_state = $lead->data_state;
    $this->lead_homephone = $lead->data_phone;
    $this->is_return = 0;
    $this->return_id = 0;

    //$this->earnings = $lead->ttl;
    $this->earnings = $postResult->priceTTL;

    $this->error_description = $postResult->errorDescription;

    $this->record_datetime = $datetime;
    $this->buyer_id = $buyerId;
    $this->buyer_channel_id = $buyerChannelId;

    $this->post_result_id = $postResult->getID();
    $this->post_result_status = $postResult->status;

  }

  public function fromReturn(T3Lead_Return $return, $datetime, $buyerId, $buyerChannelId){
      
      
    $this->lead_webmaster_id = $return->affid;
    $this->lead_webmaster_agent_id = $return->agentID;
    $this->lead_id = $return->lead_id;
    $this->lead_product = $return->product;
    $this->lead_status = '';
    $this->lead_get_method = $return->get_method;
    $this->lead_is_from_v1 = $return->from_v1;
    $this->lead_email = $return->data_email;
    $this->lead_ip = '';
    $this->lead_state = $return->data_state;
    $this->lead_homephone = $return->data_phone;
    $this->is_return = 1;
    $this->return_id = $return->id;
    $this->earnings = $return->ttl;
    $this->error_description = '';

    $this->record_datetime = $datetime;
    $this->buyer_id = $buyerId;
    $this->buyer_channel_id = $buyerChannelId;

    $this->post_result_id = null;
    $this->post_result_status = T3BuyersStats::RETURN_POST_RESULT_STATUS;

  }

  public function makeGroupIndexFieldsNotNull(){
    if(empty($this->record_date)){$this->record_date = 0;}
    if(empty($this->buyer_id)){$this->buyer_id = 0;}
    if(empty($this->buyer_channel_id)){$this->buyer_channel_id = 0;}
    if(empty($this->lead_webmaster_id)){$this->lead_webmaster_id = 0;}
    if(empty($this->lead_webmaster_agent_id)){$this->lead_webmaster_agent_id = 0;}
    if(empty($this->post_result_status)){$this->post_result_status = 0;}
  }

  public function toArray($tables = null){

    $this->makeGroupIndexFieldsNotNull();
    return parent::toArray($tables);

  }

  public function fromArray(&$array){

    parent::fromArray($array);

  }

  public function remove(){

    T3BuyersStats::getInstance()->appendToGrouped(
      mySqlDateFormat(strtotime($this->record_datetime)),
      $this->buyer_id,
      $this->buyer_channel_id,
      $this->lead_webmaster_id,
      $this->lead_webmaster_agent_id,
      $this->post_result_status,
      $this->lead_product,
      $this->lead_status,
      $this->is_return,
      $this->earnings,
      true
    );

    //$this->database->delete('buyers_statistics', 'id = ' . (int)$this->id);

  }

}




