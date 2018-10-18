<?php

class T3BuyerChannel_Report {
    static protected $insertModeRealTime = true;
    static protected $commitArray = array();
    
    /**
    * Добавить данные
    */
    static public function addItem(T3BuyerChannel_PostResult $postResult){
        // Сохраненеи в реальном времени
        $insert = array(
            'record_datetime'       => date('Y-m-d H:i:s'),
            'buyer_id'              => $postResult->buyerID,
            'buyer_channel_id'      => $postResult->buyerChannelID,
            'lead_webmaster_id'     => $postResult->publisher,
            'lead_id'               => $postResult->leadID,
            'lead_product'          => T3Products::getID($postResult->leadObject->product),
            'post_result_status'    => $postResult->status,
            'earnings'              => $postResult->priceTTL+0,
        );
        
        if(self::$insertModeRealTime){
        }
        else{ 
            // Сохранение в очередь                                                     
            self::$commitArray[] = $insert;
        }                              
    } 
     
    /**
    * Сохранить накопленные данные в базу
    */
    static public function commit($end = true){  
        if(count(self::$commitArray)){            
            self::$commitArray = array();
        }
    
        if($end) self::setInsertModeRealTime(true);
    }
    
    /**
    * Установить режим сохранения
    * 
    * @param mixed $flag
    */
    static public function setInsertModeRealTime($flag){
        self::$insertModeRealTime = (bool)$flag;
    }    
}