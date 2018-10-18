<?php

TableDescription::addTable('keywords_summary', array(
    'id',
    'date',
    'webmaster_id',
    'keyword',
    'clicks_count',
    'leads_count',
    'sold_leads_count',
    'wm',
    'ref',
    'agn',
    't3',
    'ttl'
));


  class T3Keywords_Item extends DbSerializable{

    public $id;
    public $date;
    public $webmaster_id;
    public $keyword;
    public $clicks_count;
    public $leads_count;
    public $sold_leads_count;
    public $wm;
    public $ref;
    public $agn;
    public $t3;
    public $ttl;

    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('keywords_summary');
        $this->database = T3System::getConnectCache();
    }
    
  }







