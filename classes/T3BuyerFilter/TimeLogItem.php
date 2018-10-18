<?php

TableDescription::addTable('buyers_channels_filters_time_log', array(
  'id',                       //  int(11)
  'record_datetime',          //  datetime
  'condition_id',             //  int(11)
  'condition_type_name',      //  varchar(255)
  'buyer_channel_id',         //  int(11)
  'time_length_seconds',      //  decimal(10,0)
));


class T3BuyerFilter_TimeLogItem extends DbSerializable {

  public $id;
  public $record_datetime;
  public $condition_id;
  public $condition_type_name;
  public $buyer_channel_id;
  public $time_length_seconds;


  public function __construct() {

    parent::__construct();
    $this->tables = array('buyers_channels_filters_time_log');

  }
  

  public function toArray($tables = null){

    return parent::toArray($tables);

  }


  public function fromArray(&$array){

    parent::fromArray($array);

  }


}










