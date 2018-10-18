<?php

TableDescription::addTable('payments_approvement_requests', array(
    
  'id',
  'record_datetime',
  'webmaster_id',
  'reason_object',
  'reason_object_text',
  'payment_created',
  'payment_id',
  'process_status',
  'process_datetime',
  'process_user_id',
    
));

class T3Payments_ApprovementRequest extends DbSerializable {

  const STATUS_not_requested = 'not_requested';
  const STATUS_not_processed = 'not_processed';
  const STATUS_approved = 'approved';
  const STATUS_disapproved = 'disapproved';
  
  
  public $id;
  public $record_datetime;
  public $webmaster_id;
  public $reason_object;
  public $reason_object_text;
  public $payment_created = 0;
  public $payment_id;
  public $process_status = T3Payments_ApprovementRequest::STATUS_not_processed;
  public $process_datetime;
  public $process_user_id;
  
    public function __construct() {

        if(!isset($this->className))$this->className = __CLASS__;

        parent::__construct();

        $this->tables = array('payments_approvement_requests');


    }  
  

}




