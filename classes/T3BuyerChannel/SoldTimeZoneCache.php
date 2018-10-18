<?php

class T3BuyerChannel_SoldTimeZoneCache {
    static public function addSoldLead_Abstract($date_TZ, $postingNew){
        $postingNew = (int)$postingNew;
        $where = "`date` = STR_TO_DATE('{$date_TZ}', '%Y-%m-%d') and idPostingNew='{$postingNew}'";
        
        $result = T3Db::api()->fetchAll(
            "select `date`,idPostingNew from buyer_channel_timezone_sold_cache where {$where}" 
        );  
        
        if(count($result) == 0){
            // создаем запись
            T3Db::api()->insert("buyer_channel_timezone_sold_cache", array(
                'date'          => $date_TZ,    
                'idPostingNew'  => $postingNew,
                'count'         => '1',
            ));
        }
        else if(count($result)){
            // изменяем запись
            T3Db::api()->update("buyer_channel_timezone_sold_cache", array(
                'count'         => new Zend_Db_Expr("`count`+1"),
            ), $where);  
        } 
    } 
    
    static public function addSoldLead(T3BuyerChannel $posting, $datetime_PST = null){
        if(is_null($datetime_PST))$datetime_PST = date("Y-m-d H:i:s");
        
        $postingNew = $posting->id;
        $dateTZ = substr(TimeZoneTranslate::translate('pst', $posting->timezone, $datetime_PST), 0, 10);
             
        self::addSoldLead_Abstract($dateTZ, $postingNew);    
    }
    
    // возвращает количество проданных лидов за день
    static public function getSoldLeads(T3BuyerChannel $posting, $datetime_PST = null){
        if(is_null($datetime_PST))$datetime_PST = date("Y-m-d H:i:s"); 
        $dateTZ = substr(TimeZoneTranslate::translate('pst', $posting->timezone, $datetime_PST), 0, 10);

        if (T3AutoPingPost::isPingPostProduct($posting->product)) {
            $post_id = T3AutoPingPost::getRelevantChannel($posting->id);
            if ($post_id) {
                return (int)T3Db::api()->fetchOne(
                    "select `count` from buyer_channel_timezone_sold_cache where `date` = STR_TO_DATE('{$dateTZ}', '%Y-%m-%d') and idPostingNew IN ($posting->id, $post_id) order by `count` desc limit 1");
            }
        }

        return (int)T3Db::api()->fetchOne(
            "select `count` from buyer_channel_timezone_sold_cache where `date` = STR_TO_DATE('{$dateTZ}', '%Y-%m-%d') and idPostingNew = ? order by `count` desc limit 1", 
            $posting->id
        );        
    }
    
    static public function getSoldLeadsByOldID($posting_id, $date){
        return 0;      
    }   
}