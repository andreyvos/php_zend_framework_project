<?php


class T3SimpleDbSelect extends SimpleDbSelect {

    public function  __construct($where = null, $order = null) {
      parent::__construct(T3Db::api(), $where, $order);
    }

    public static function adjustStatic($select, $where = array(), $order = array()){  
      return parent::adjustStatic($select, T3Db::api(), $where, $order);
    }

    public static function select($table, $where = array(), $order = array()){
      return parent::select($table, T3Db::api(), $where, $order);
    }

}