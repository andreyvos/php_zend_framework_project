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

TableDescription::addTable('buyers_invoices_addings', array(
  'id',                       //  int(11)
  'invoice_id',               //  int(11)
  'buyer_id',                 //  int(11)
  'action_datetime',          //  datetime
  'action_sum',               //  decimal(10,2)
  'comment',                  //  text
));

class T3Invoice_Adding extends DbSerializable {

  public $id;
  public $invoice_id;
  public $buyer_id;
  public $action_datetime;
  public $action_sum;
  public $comment;

  public function  __construct() {
    if (!isset($this->className))$this->className = __CLASS__;
    parent::__construct();
    $this->tables = array('buyers_invoices_addings');

  }

}



