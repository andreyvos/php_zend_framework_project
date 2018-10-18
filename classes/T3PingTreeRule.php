<?php

TableDescription::addTable('pingtrees_allocation', array(
    'id',
    'product',
    'channel_type',
    'idcompany',
    'channel',
    'scheme',
    'status',                                               
));


class T3PingTreeRule extends DbSerializable {

    public $id;
    public $product; 
    public $channel_type; 
    public $idcompany; 
    public $channel; 
    public $scheme; 
    public $status; 
    

    public function  __construct() {
        if (!isset($this->className)) $this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('pingtrees_allocation');
    }
}