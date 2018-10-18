<?php

TableDescription::addTable('webmasters_payments_holds', array(
  'webmaster_id',             //  int(11)
  'holds_used',               //  set('leads','bonuses')
  'holds',                    //  int(11)
  'fee',
), 
'webmaster_id');


class T3Payments_Holds extends DbSerializable {

  public $id;

  public $webmaster_id;
  public $holds_used;
  public $holds;
  public $fee = '1';


  public $webmaster;
  public $holdsAr;
  public $holdsUsedAr;
 
  
  /**
  * Массив типов холда
  */
  static public function getHoldTypes(){
      $obj = new self();
      return $obj->holdsTypes;    
  }

  public function __construct() {
    parent::__construct();
    $this->id = & $this->webmaster_id;
    $this->tables = array('webmasters_payments_holds');
    
    $this->readNewIdAfterInserting = false;

    $this->holdsAr = array();
    $this->holdsUsedAr = array();
    $this->readNewIdAfterInserting = false;
    
  }

  public function getDistributiveUsed(){
    $result = array();
    foreach(T3Payments::$parts as $v)
      $result[$v] = false;
    foreach($this->holdsUsedAr as $v)
      $result[$v] = true;
    return $result;
  }

  public function getWebmaster($lazy = true){
    if($lazy && $this->webmaster !== null)
      return $this->webmaster;
    $this->webmaster = new T3WebmasterCompany();
    $this->webmaster->fromDatabase($this->webmaster_id);
    return $this->webmaster;
  }

  public function getMaximumHold(){
    $max = -1;
    foreach(T3Payments::$parts as $part){
      $n = $this->getHold($part);
      if($n>$max)
        $max = $n;
    }
    return $max;
  }

  public function getHold($type){
    if(in_array($type, $this->holdsUsedAr) && $this->holdsAr[$type] !== null)
      return $this->holdsAr[$type];
    $default = T3Payments::getInstance()->getDefaultHolds();
    return $default[$type];
  }

  public function getUseDefault($type){
    return !in_array($type, $this->holdsUsedAr);
  }

  public function fromArray(&$array){
    parent::fromArray($array);
    $this->holdsUsedAr = unserialize($this->holds_used);
    if($this->holdsUsedAr === false)
      $this->holdsUsedAr = array();
    $this->holdsAr = unserialize($this->holds);
    if($this->holdsAr === false)
      $this->holdsAr = array();
    $defaults = T3Payments::getInstance()->getDefaultHolds();
    foreach(T3Payments::$parts as $part)
      if(!array_key_exists($part, $this->holdsAr))
        $this->holdsAr[$part] = $defaults[$part];
  }

  public function useHold($part, $use){
    $in = in_array($part, $this->holdsUsedAr);
    if($use == $in)
      return;
    if(!$use)
      unset($this->holdsUsedAr[array_search($part, $this->holdsUsedAr)]);
    else
      $this->holdsUsedAr[] = $part;
  }

  public function toArray($tables = null){
    $this->holds_used = serialize($this->holdsUsedAr);
    $this->holds = serialize($this->holdsAr);
    return parent::toArray($tables);
  }

}


