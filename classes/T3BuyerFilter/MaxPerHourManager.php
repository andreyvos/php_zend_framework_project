<?php

class T3BuyerFilter_MaxPerHourManager {

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


    public function acceptsLead($buyerChannelId, $maxPerHourCount){
        try{
            if($this->database->fetchOne('select count(*) from buyers_filters_max_per_hour where buyer_channel_id = ?', array($buyerChannelId)) == 0){
                return true;
            }
        }
        catch(Exception $e){}

        return $this->database->fetchOne(
            'select count(*) from buyers_filters_max_per_hour where buyer_channel_id = ? and (leads_sold < ? or substr(?, 1, 13)!=substr(record_datetime, 1, 13))', 
            array($buyerChannelId, $maxPerHourCount, mySqlDateTimeFormat())
        ) > 0;
    }


    public function recordLead($buyerChannelId){   
        $datetimeMysql = $this->database->fetchOne("select record_datetime from buyers_filters_max_per_hour where buyer_channel_id = ?", array($buyerChannelId));

        if(empty($datetimeMysql)){    
            $this->database->query(
                "insert into buyers_filters_max_per_hour set buyer_channel_id = ?, record_datetime = ?, leads_sold = '1'", 
                array($buyerChannelId, mySqlDateTimeFormat())
            ); 
        }
        else{

            $nowMysql = mySqlDateTimeFormat();
            $datetimeMysqlHour = substr($datetimeMysql, 0, 13);
            $nowMysqlHour = substr($nowMysql, 0, 13);

            if($datetimeMysqlHour==$nowMysqlHour){
                $this->database->query(
                    "update buyers_filters_max_per_hour set leads_sold = leads_sold + 1 where buyer_channel_id = ?", 
                    array($buyerChannelId)
                ); 
            }
            else{
                $this->database->query(
                    "update buyers_filters_max_per_hour set record_datetime = ?, leads_sold = 1 where buyer_channel_id = ?", 
                    array($nowMysql, $buyerChannelId)
                );
            } 
        }   
    }     
}

T3BuyerFilter_MaxPerHourManager::getInstance();