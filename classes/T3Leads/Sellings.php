<?php

class T3Leads_Sellings {

  protected static $_instance = null;

  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function getSellings_Array($conditions = array(), $order = array()){
    return T3SimpleDbSelect::select('buyers_leads_sellings', $conditions, $order)->fetchAll();
  }

  static public function sellLead(T3Lead $lead, T3BuyerChannel $buyerChannel, $postLogItem, $buyerSum, $sendDate = null) {

    if($sendDate === null)
      $sendDate = mySqlDateTimeFormat();

    $selling = new T3Leads_Selling();

    $selling->lead_id                =   $lead->id;
    $selling->channel_id             =   $buyerChannel->id;
    $selling->buyer_id               =   $buyerChannel->buyer_id;
    $selling->invoice_id             =   null;
    $selling->posting_log_record_id  =   $postLogItem;
    $selling->action_datetime        =   $sendDate;
    $selling->action_sum             =   $buyerSum;
    $selling->lead_email             =   $lead->data_email;
    $selling->lead_home_phone        =   $lead->data_phone;
    $selling->lead_product           =   $lead->product;

    $selling->insertIntoDatabase();

    return $selling;
    
  }

}

