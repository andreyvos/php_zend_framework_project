<?php

TableDescription::addTable('webmasters_payments_pays', array(
  'id',                       //  int(11)
  'payment_id',               //  int(11)
  'webmaster_id',             //  int(11)
  'record_datetime',          //  datetime
  'user_id',                  //  int(11)
  'user_ip_address',          //  varchar(50)
  'successive_id',            //  int(11)
  'pay_system',               //  varchar(255)
  'value',                    //  decimal(10,2)
  'value_without_fee',        //  decimal(10,2)
  'fee',                      //  decimal(10,2)
  'pay_systems_data',
  'current_system_data',
));

class T3Payments_Pay extends DbSerializable {

  public $id;
  public $payment_id;
  public $webmaster_id;
  public $record_datetime;
  public $user_id;
  public $user_ip_address;
  public $successive_id;
  public $pay_system;
  public $value;
  public $value_without_fee;
  public $fee;
  public $pay_systems_data;
  public $current_system_data;

  public function __construct() {

    parent::__construct();
    $this->tables = array('webmasters_payments_pays');

  }


  /*public function fromArray(&$array){
    parent::fromArray($array);
  }*/

  /*public function toArray($tables = null){
    return parent::toArray($tables);
  }*/

 
}

















