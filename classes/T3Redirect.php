<?php

TableDescription::addTable('post_redirect', array(
                               'id',
                               'idlead',
                               'clientIP',
                               'clientIPint',
                               'redirectIP',
                               'redirectIPint',
                               'redirectComplite',
                               'matchRedirectIP',
                               'rediretType',
                               'createDate',
                               'redirectDate',
                               'redirectURL',
                               'postingID',
                               'channelID',
                               'buyerID',
                               'webmasterID',
                               'WMasterSubAccountID',
                           ));

class T3Redirect extends DbSerializable {
    public $id;
    public $idlead;
    public $clientIP;
    public $clientIPint;
    public $redirectIP;
    public $redirectIPint;
    public $redirectComplite;
    public $matchRedirectIP = 0;
    protected $rediretType;
    public $createDate;
    public $redirectDate;
    public $redirectURL;
    public $postingID;
    public $channelID;
    public $buyerID;
    public $webmasterID;
    public $WMasterSubAccountID;


    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();

        $this->tables = array('post_redirect');
        //$this->tableName = "post_redirect";
        //$this->fieldsArrayFlip['id'] = "id";
    }

    public function getRediretType() {
        if (!isset($this->rediretType) || is_null($this->rediretType) || $this->rediretType == "") {
            // выбор типа редиректа
            $this->rediretType = "default";
            $this->saveToDatabase();
        }

        return $this->rediretType;
    }

    public function redirectToURL() {
        if ($this->redirectComplite == 0) {
            $this->redirectDate = date("Y-m-d H:i:s");
            $this->redirectComplite = "1";
            $this->redirectIP = $_SERVER['REMOTE_ADDR'];
            $this->redirectIPint = myHttp::get_ip_num($_SERVER['REMOTE_ADDR']);
            $this->saveToDatabase();

            header("location: {$this->redirectURL}");
        }

        exit();
    }
}