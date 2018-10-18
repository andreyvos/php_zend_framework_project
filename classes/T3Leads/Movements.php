<?php

class T3Leads_Movements {

  protected static $_instance = null;

  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function getMovements_Array($conditions = array(), $order = array()){
    return T3SimpleDbSelect::select('buyers_leads_movements', $conditions, $order)->fetchAll();
  }

}

