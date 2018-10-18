<?php

TableDescription::addTable('buyers_returns_pings', array(
  'id',                       //  int(11)
  'record_datetime',          //  datetime
  'buyer_id',                 //  int(11)
  'buyer_channel_id',         //  int(11)
  'authentication_key',       //  varchar(255)
  'sending_to_call_center_datetime',//  datetime
  'lead_id',                  //  int(11)
  'lead_email',               //  varchar(255)
  'lead_homephone',           //  varchar(255)
  'posted_lead_datetime',     //  datetime
  'return_reason',            //  text
  'ambiguity_leads_ids',      //  varchar(255)
  'ambiguity_message_sending_datetime',//  datetime
  'ambiguity_message_text',   //  text
  'lead_identification_undertaken',//  tinyint(1)
  'cannot_be_identified',     //  tinyint(1)
  'has_ambiguity',            //  tinyint(1)
  'ambiguity_message_sent',   //  tinyint(1)
  'ambiguity_resolved',       //  tinyint(1)
  'lead_identified',          //  tinyint(1)
  'sent_to_call_center',      //  tinyint(1)
  'call_center_transaction_id',
  'recieved_from_call_center',//  tinyint(1)
  'call_center_approved',
  'approvement_made_by_admin',//  tinyint(1)
  'approvement_undertaken',   //  tinyint(1)
  'approved',                 //  tinyint(1)
  'sent_to_returns',          //  tinyint(1)
  'sending_to_returns_datetime',
  'removed',                  //  tinyint(1)
));


class T3BuyerReturnPingItem extends DbSerializable{

  public $id;
  public $record_datetime;
  public $buyer_id;
  public $buyer_channel_id = 0;
  public $authentication_key;
  public $sending_to_call_center_datetime = '';
  public $lead_id = 0;
  public $lead_email;
  public $lead_homephone;
  public $posted_lead_datetime;
  public $return_reason;
  public $ambiguity_leads_ids;
  public $ambiguity_message_sending_datetime;
  public $ambiguity_message_text;
  public $lead_identification_undertaken = 0;
  public $cannot_be_identified = 0;
  public $has_ambiguity = 0;
  public $ambiguity_message_sent = 0;
  public $ambiguity_resolved = 0;
  public $lead_identified = 0;
  public $sent_to_call_center = 0;
  public $call_center_transaction_id;
  public $recieved_from_call_center = 0;
  public $call_center_approved = 0;
  public $approvement_made_by_admin = 0;
  public $approvement_undertaken = 0;
  public $approved = 0;
  public $sent_to_returns = 0;
  public $sending_to_returns_datetime;
  public $removed = 0;


  public $ambiguityLeadsIds = array();

  public function __construct() {

    parent::__construct();
    $this->tables = array('buyers_returns_pings');

  }

  public function toArray($tables = null){

    $this->ambiguity_leads_ids = serialize($this->ambiguityLeadsIds);

    return parent::toArray($tables);

  }

  public function fromArray(&$array){

    parent::fromArray($array);

    $this->ambiguityLeadsIds = unserialize($this->ambiguity_leads_ids);

  }

}




