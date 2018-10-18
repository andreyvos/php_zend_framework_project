<?php

class T3BuyerFilter_MaxFrequencyManager {

    protected static $_instance;
    public $database;

    protected function __construct(){
      $this->database = T3Db::api();
    }

    /** @return T3BuyerFilter_MaxFrequencyManager */
    public static function getInstance() {
      if (is_null(self::$_instance)) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }


    public function acceptsLead($buyerChannelId){

      return $this->database->fetchOne('select count(*) from buyers_filters_max_frequency
        where buyer_channel_id=? and freeze_finish_datetime>?
      ', array($buyerChannelId, mySqlDateTimeFormat())) == 0;

    }


    public function recordLead($buyerChannelId, $minutesPeriod = 0){

      $zd = new Zend_Date();
      $nowMySql = $zd->toString(MYSQL_DATETIME_FORMAT_ZEND);
      $zd->addMinute($minutesPeriod);
      $finishMySql = $zd->toString(MYSQL_DATETIME_FORMAT_ZEND);

      $this->database->query('
        replace into buyers_filters_max_frequency
        set
          record_datetime = ?,
          buyer_channel_id = ?,
          minutes_period = ?,
          freeze_finish_datetime = ?
      ', array($nowMySql, $buyerChannelId, $minutesPeriod, $finishMySql));

    }



}

T3BuyerFilter_MaxFrequencyManager::getInstance();
