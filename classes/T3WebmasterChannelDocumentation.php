<?php


TableDescription::addTable('server_post_documentations', array(
  'id',
  'product',
  'show',
));


class T3WebmasterChannelDocumentation extends DbSerializable {

    public $id;
    public $product; 
    public $show;

    public function  __construct() {
        if (!isset($this->className)) $this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('server_post_documentations');
    }

    
}