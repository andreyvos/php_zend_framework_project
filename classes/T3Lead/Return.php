<?php

TableDescription::addTable('leads_returns', array(
    'id',

    'user_id',
    'user_ip_address',

    'wm_show',
    'lead_id',
    'movement_id',
    
    'from_v1',
    'product',
    'get_method',
    'channel_id',
    'subacc',
    
    'invoiceItemType',
    'invoiceItemID',
    'buyer',
    'posting',
    
    'affid',
    'refaffid',
    'agentID',
    
    'wm',
    'ref',
    'agn',
    'ttl',
    
    'lead_datetime',
    'return_datetime',
    
    'data_email',
    'data_phone',
    'data_ssn',
    'data_state',
    
    'comment',
));

class T3Lead_Return extends DbSerializable{
    public $id;

    public $user_id;
    public $user_ip_address;

    public $wm_show = 1; // некоторые ретурны не вычитаются с вебмастера, поэтому ему не надо показывать их.
    public $lead_id;
    public $movement_id;
    
    public $from_v1 = 0;
    public $product;
    public $get_method;
    public $channel_id = 0; 
    public $subacc = 0;
    
    public $invoiceItemType;
    public $invoiceItemID;
    public $buyer;
    public $posting;
    
    public $affid;
    public $refaffid = 0;
    public $agentID = 0;
    
    public $wm = 0;
    public $ref = 0;
    public $agn = 0;
    public $ttl = 0;
    
    public $lead_datetime;
    public $return_datetime;
    
    /*
    public $user_source;
    public $user_keyword;
    public $user_referref;
    public $user_parsed_source;
    public $user_parsed_keyword;
    */
    
    public $data_email;
    public $data_phone;
    public $data_ssn;
    public $data_state;
    
    public $comment;

    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('leads_returns');
    }
}

