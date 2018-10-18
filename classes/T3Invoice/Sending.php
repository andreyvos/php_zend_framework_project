<?php

TableDescription::addTable('buyers_invoices_sendings', array(
  'id',                       //  int(11) unsigned
  'invoice_id',               //  int(11) unsigned
  'buyer_id',
  'author_id',                //  int(11) unsigned
  'is_reminding',             //  tinyint(1)
  'automatic',                //  tinyint(1)
  'sending_datetime',         //  datetime
  'email',                    //  varchar(250)
  'sending_text',             //  longtext
  'attached_file_title',
  'attached_file_name',       //  text
  'attached_file_name_link',  //  text
));


class T3Invoice_Sending extends DbSerializable{

  public $id;
  public $invoice_id;
  public $buyer_id;
  public $author_id;
  public $is_reminding;
  public $automatic;
  public $sending_datetime;
  public $email;
  public $sending_text;
  public $attached_file_title;
  public $attached_file_name;
  public $attached_file_name_link;

  public $invoice;

  public function  __construct($invoice = null) {

    if (!isset($this->className))$this->className = __CLASS__;

    parent::__construct();
    $this->tables = array('buyers_invoices_sendings');

    $this->invoice = $invoice;
  }

  public function getBuyer($lazy = true){
    if($lazy && $this->buyer !== null)
      return $this->buyer;

    $this->buyer = $this->database->fetchRow('
      SELECT ucb.*
      FROM buyers_invoices_sendings as bic
      LEFT JOIN buyers_invoices as bi
      ON bic.invoice_id = bi.id
      LEFT JOIN users_company_buyer as ucb
      ON bi.buyer_id = ucb.id
      WHERE bic.id = ?
    ', array($this->id));

    return $this->buyer;

  }

  public function toArray($tables = null){

    return parent::toArray($tables);

  }

  public function fromArray(&$array){

    parent::fromArray($array);


  }

  public function getBuyerId(){
    $a = $this->getBuyer();
    if($a === false)
      return false;
    return $a['id'];
  }

  public function getBuyerSystemName(){
    $a = $this->getBuyer();
    if($a === false)
      return false;
    return $a['systemName'];
  }

}

