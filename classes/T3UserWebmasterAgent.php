<?php

TableDescription::addTable('users_webmaster_agents', array(
    'id',

    'distributionRate',

    'procentADM',
    'procentWM',

    'contactPhone',
    'contactEmail',
    'contactAIM',
    'contactSkype',
    'contactICQ',
    'contactYahoo',

    'balance',
    
    'manager',

));  

class T3UserWebmasterAgent extends DbSerializable {
    public $id;

    public $distributionRate;

    public $procentADM;
    public $procentWM;

    public $contactPhone;
    public $contactEmail;
    public $contactAIM;
    public $contactSkype;
    public $contactICQ;
    public $contactYahoo;

    public $balance;
    
    public $manager = 0;

    /**
    * Получение объекта пользователя
    * @return T3User
    */
    public function getUser(){
        return T3Users::getUserById($this->id);
    }

    public function  __construct() {

	    if (!isset($this->className)) $this->className = __CLASS__;
	    parent::__construct();
	    $this->tables = array('users_webmaster_agents');
        $this->readNewIdAfterInserting = false;
    }
    
    public function getUsers() {
	    //$select = $this->database->select()->from(array("w" => "users_company_webmaster"))->where('agentID=?', $this->id);
	    //$comps = $this->database->query($select)->fetchAll();
	    $users = array();
	    /*
        foreach($comps as $v) {
	        $select = $this->database->select()->from(array("u"=>"users"))->where("company_id=?",(int)$v["id"])->where('role=?','webmaster');
	        $usersInCompany = $this->database->query($select)->fetchAll();
	    
            foreach($usersInCompany as $v) {
		        $users[]=$v;
	        }
	    }
        */
	    return $users;
    }
    
    /**
    * Получить массив подчиненных агентов. Массив состоит из их ID
    * 
    * @return array
    */
    public function getSlaveAgents(){
        return T3Db::api()->fetchCol("select id from users_webmaster_agents where manager=?", $this->id);    
    }


}

