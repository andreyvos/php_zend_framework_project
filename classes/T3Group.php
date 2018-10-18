<?php
/*
TableDescription::addTable('users_groupnames',array(
                               'id',
                               'system_name',
                               'title',
                               'description',
                           ));

class T3Group extends DbSerializable {

    public $id;
    public $system_name;
    public $title;
    public $description;

    public function  __construct() {

        if (!isset($this->className))$this->className = __CLASS__;

        parent::__construct();
        $this->tables = array('users_groupnames');

    }

    public function getUsers_Array() {
      $ar = $this->database->fetchAll("
        SELECT user_id
        FROM 
        WHERE group_system_name = ?
      ", array($this->system_name));
      $result = array();
      foreach($ar as &$v)
      $result[] = $v['user_id'];
      return $result;
    }

    public static function createFromDatabase($conditions) {
        return self::createFromDatabaseByClass($conditions, __CLASS__);
    }

    public static function createFromArray(&$array) {
        return self::createFromArrayByClass($array, __CLASS__);
    }
} 
*/