<?php

TableDescription::addTable('channels_post', array(
  'id',                       //  int(1) unsigned
  'num',
  'getID',
  'password',                 //  varchar(32)
  'company_id',               //  int(1)
  'documentation_key',        //  varchar(255)
  'title',                    //  varchar(255)
  'product',                  //  varchar(64)
  'status',                   //  enum('just_created','verification','active','paused','deleted')
  'url',                      //  varchar(255)
  'traffic_sources',          //  text
  'creation_datetime',        //  datetime
));

class T3Channel_Post extends DbSerializable{

    public $id;
    public $num;
    public $getID;
    public $password;
    public $company_id;
    public $documentation_key;
    public $title;
    public $product;
    public $status;
    public $url;
    public $traffic_sources;
    public $creation_datetime;

    public function  __construct(){
        if(!isset($this->className)) $this->className = __CLASS__;
        parent::__construct();
        $this->tables[] = 'channels_post';  
        
        $this->type = 'post_channel'; 
    } 
}