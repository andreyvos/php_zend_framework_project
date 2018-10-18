<?php

TableDescription::addTable('buyers_leads_movements', array(
  'id',                       //  int(11) unsigned
  'action_type',              //  enum('reject','price_changing')
  'lead_id',                  //  int(11) unsigned
  'channel_id',               //  int(11) unsigned
  'buyer_id',                 //  int(11)
  'invoice_id',               //  int(11) unsigned
  'posting_log_record_id',    //  int(11) unsigned
  'action_datetime',          //  datetime
  'action_sum',               //  decimal(10,2)
  'lead_email',               //  varchar(255)
  'lead_ssn',                 //  varchar(9)
  'lead_home_phone',          //  varchar(20)
  'lead_product',             //  varchar(32)
));

class T3Leads_Movement extends DbSerializable{

  public $id;
  public $action_type;
  public $lead_id;
  public $channel_id;
  public $buyer_id;
  public $invoice_id;
  public $posting_log_record_id;
  public $action_datetime;
  public $action_sum;
  public $lead_email;
  public $lead_ssn;
  public $lead_home_phone;
  public $lead_product;

  public function __construct() {

    parent::__construct();

    $this->tables = array('leads_data');

  }

  
}

