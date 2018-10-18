<?php
/**
* Класс Одной оплаты за инвойс
* 
* В этом классе присутсвуют данные о:
*   1. одной оплате за инвойс
*   1.1 Сумма
*   1.2 Дата
*   1.3 Комментарии
* 
* В один инвйос может входить несколько оплат
* В 99% случаях на 1 инвйос будет 1 оплата (сразу вся сумма)
*/

TableDescription::addTable('buyers_invoices_payments', array(
  'id',                       //  int(11) unsigned
  'invoice_id',               //  int(11)
  'pay_datetime',             //  datetime
  'pay_sum',                  //  decimal(10,2)
  'comment',                  //  longtext
));

class T3Invoice_Payment extends DbSerializable{

  public $id;
  public $invoice_id;
  public $pay_datetime;
  public $pay_sum;
  public $comment;

  public $invoice;

  protected $buyer;

  public function  __construct() {

    
    if (!isset($this->className))$this->className = __CLASS__;

    parent::__construct();
    $this->tables = array('buyers_invoices_payments');
  }

  public function getInvoice($lazy = true){
    if($lazy && $this->invoice !== null)
      return $this->invoice;
    $this->invoice = new T3Invoice();
    $this->invoice->fromDatabase($this->invoice_id);
    return $this->invoice;
  }
  
  public function change($sum, $date){

    try{

      $this->database->beginTransaction();

      $sumDelta = $this->pay_sum - $sum;

      $this->pay_sum = $sum;
      $this->pay_datetime = $date;
      $this->saveToDatabase();

      $this->updateInvoiceAndBuyer($sumDelta);

      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      throw $e;
    }

    return true;

  }

  public function remove(){

    try{

      $this->database->beginTransaction();

      $this->deleteFromDatabase();

      $this->updateInvoiceAndBuyer($this->pay_sum);
 
      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      throw $e;
    }

    return true;

  }

  function updateInvoiceAndBuyer($sumDelta){

    $lastPayDateTime = $this->database->fetchOne('
      select max(pay_datetime) from buyers_invoices_payments where invoice_id=?
    ', array($this->invoice_id));

    $invoice = $this->getInvoice();

    $invoice->paid_sum -= $sumDelta;
    $invoice->fully_paid = (int)($invoice->paid_sum >= $invoice->total_value);
    $invoice->last_payment_datetime = $lastPayDateTime;

    $invoice->saveToDatabase();

    $this->database->query('
      UPDATE users_company_buyer
      SET balance = balance - ?
      WHERE id = ?
    ', array($sumDelta, $invoice->buyer_id));

  }

}







