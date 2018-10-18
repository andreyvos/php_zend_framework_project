<?php

TableDescription::addTable('leads_type', array(
  'id',                       //  int(1) unsigned
  'name',                     //  varchar(32)
  'title',                    //  varchar(128)
  'class_body',               //  varchar(128)
  'activ',                    //  enum('0','1')
  'offline_vefification',     //  enum('0','1')
  'buyer_reg_view',           //  enum('0','1')
  'seller_list_view',         //  enum('0','1')
  'groupid',                  //  int(1) unsigned
  'prioritet',                //  int(1)
  'best',                     //  enum('0','1')
  'new',                      //  enum('0','1')
  'serverPost',               //  enum('0','1')
));


class T3Banners_Product extends DbSerializable {

  public $id;
  public $name;
  public $title;
  public $class_body;
  public $activ;
  public $offline_vefification;
  public $buyer_reg_view;
  public $seller_list_view;
  public $groupid;
  public $prioritet;
  public $best;
  public $new;
  public $serverPost;

  public function __construct() {

    parent::__construct();

    $this->tables = array('leads_type');

  }


}
