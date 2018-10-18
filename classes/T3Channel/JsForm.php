<?php

TableDescription::addTable('channels_js_forms', array(
    'id',                       //  int(1) unsigned
    'url',                      //  varchar(255)
    'url_domain',               //  varchar(255)
    'url_domain_and_path',      //  varbinary(255)
    'is_t3cms',                 //  tinyint(1)
    'accepts_referrers',        //  tinyint(1)
    'product',                  //  varchar(64)
    'title',                    //  varchar(255)
    'company_id',               //  int(1)
    'status',                   //  enum('active','locked','deleted')
    'verification_rate',        //  int(1)
    'is_mobile',
));


class T3Channel_JsForm extends DbSerializable{ // T3Channel_Abstract

  public $id;
  public $url;
  public $url_domain;
  public $url_domain_and_path;
  public $is_t3cms;
  public $accepts_referrers;
  public $product;
  public $title;
  public $company_id;
  public $status;
  public $verification_rate;
  public $is_mobile = 0;


  public function  __construct() {    
    if(!isset($this->className))
      $this->className = __CLASS__;
    parent::__construct();
    $this->tables[] = 'channels_js_forms';

    $this->type = 'js_form';
  }

  protected function fillUrlParts(){
    $b = !is_null($this->url);
    
    $url = "";
    $this->url = str_replace("100%", urlencode("100%"), $this->url);
    
    /*if($b)
    {
        try
        {
            $url = Zend_Uri::factory($this->url);
            $b = $url->valid();
        }
        catch(Exception $e)
        {
            echo "'".($this->url)."'\r\n";
            echo $e->getMessage()."\r\n";
            exit();
        }
    }*/

    if($b)
    {
      //$this->url_domain = $url->getHost();

      $domainAndPath2 = parse_url($this->url);
      $this->url_domain = $domainAndPath2['host'];
      $domainAndPath2 = $domainAndPath2['host'].$domainAndPath2['path'];
      $this->url_domain_and_path = $domainAndPath2;
      
      /*
      $this->url_domain_and_path = $url->getHost() . $url->getPath();
      if($this->url_domain_and_path[strlen($this->url_domain_and_path)-1] !== '/')
        $this->url_domain_and_path .= '/';
      */
    }else{
      $this->urlDomain = null;
      $this->url_domain_second_level = null;
      $this->url_domain_and_path = null;      
    }
  }

  public function toArray($tables = null){
    $this->fillUrlParts();
    return parent::toArray($tables);
  }

  public function setUrl($url)
  {
    $this->url = $url;
    $this->fillUrlParts();
  }

  public static function createFromRequest($userId, $referrer, $product){
    $object = new self();

    $object->id = null;
    $object->company_id = $userId;
    $object->status = 'active';
    $object->creation_datetime = mySqlDateTimeFormat();
    $object->product = $product;

    $referrer = strtolower($referrer);

    $object->setUrl($referrer);

    return $object;
  }

  public static function createFromDatabase($conditions){
    return self::createFromDatabaseByClass($conditions, __CLASS__);
  }

  public static function createFromArray(&$array){
    return self::createFromArrayByClass($array, __CLASS__);
  }

}
