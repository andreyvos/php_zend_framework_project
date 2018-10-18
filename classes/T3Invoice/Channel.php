<?php
/**
* Класс Канала входящего в Определенный Инвойс
* 
* В этом классе присутсвуют данные о:
*   1. периоде за который делался данный инвойс
*   2. Всех лидах, за которые был выставлен этот инвйос
*   3. Всех изменений цен за этот период
* 
* В один инвойс может входить несколько каналов. 
*/

TableDescription::addTable('buyers_invoices_channels', array(
  'id',
  'invoice_id',               //  int(11) unsigned
  'channel_id',               //  int(11) unsigned
  'leads_number',             //  int(11)
  'movements_number',         //  int(11)
  'total_value',              //  decimal(10,2)
));

class T3Invoice_Channel extends DbSerializable {

  public $id;
  public $invoice_id;
  public $channel_id;
  public $leads_number;
  public $movements_number;
  public $total_value;

  public $invoice;

  protected $buyer;

  public function  __construct($invoice = null) {


    if (!isset($this->className))$this->className = __CLASS__;

    parent::__construct();
    $this->tables = array('buyers_invoices_channels');


    $this->invoice = $invoice;
  }

  public function getBuyer($lazy = true){
    if($lazy && $this->buyer !== null)
      return $this->buyer;

    $this->buyer = $this->database->fetchRow('
      SELECT ucb.*
      FROM buyers_invoices_channels as bic
      LEFT JOIN buyers_invoices as bi
      ON bic.invoice_id = bi.id
      LEFT JOIN users_company_buyer as ucb
      ON bi.buyer_id = ucb.id
      WHERE bic.id = ?
    ', array($this->id));

    return $this->buyer;

  }

}



