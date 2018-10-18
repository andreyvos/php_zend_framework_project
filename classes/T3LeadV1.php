<?php

TableDescription::addTable('stat', array(
    'idlead',
    'type',
    'affid',
    'refaffid',
    'money',
    'refmoney',
    'totalmoney',
    'listManMoney',
    'subacc',
    'keyword',
    'leaddatetime',
    'feedid',
    'email',
    'zip',
    'state',
    'min_price',
    'status',
    't3verify',
    'verify_type',
    'comment_vf',
    'loginAgent',
    'agentProcent',
    'agentMoney',
    'sysMoneyProcentWM',
    'finfo',
    'fdomain',
    'fkeyword',
    'channel_type',
    'subscribe_use',
    'hash',
    'quality',
    'revUpfromID',
    'clientIP',
    'redirects_count',
    'redirects_success',
), 'idlead');

class T3LeadV1 extends DbSerializable{
    public $idlead;
    public $type;
    public $affid;
    public $refaffid;
    public $money;
    public $refmoney;
    public $totalmoney;
    public $listManMoney; 
    public $subacc;
    public $keyword;
    public $leaddatetime;
    public $feedid;
    public $email;
    public $zip;
    public $state;
    public $min_price;
    public $status;
    public $t3verify;
    public $verify_type;
    public $comment_vf;
    public $loginAgent;
    public $agentProcent;
    public $agentMoney;
    public $sysMoneyProcentWM;
    public $finfo;
    public $fdomain;
    public $fkeyword;
    public $channel_type;
    public $subscribe_use;
    public $hash;
    public $quality;
    public $revUpfromID;
    public $clientIP;
    public $redirects_count;
    public $redirects_success;
    

    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();

        $this->database = T3Db::v1();
        $this->tables = array('stat');   
    }
    
    public function getResponses(){
        return $this->database->fetchAll("select * from posted where posted.unique=?", $this->idlead);    
    }
     
}


