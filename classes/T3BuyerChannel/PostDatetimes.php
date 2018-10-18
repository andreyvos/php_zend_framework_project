<?php

class T3BuyerChannel_PostDatetimes {
    static protected $insertModeRealTime = true;
    static protected $commitArray = array();
    
    /**
    * Добавить данные
    * Они или сразу записываются в базу или добавляются в очедеь, которую в дальнейшем можно будет сохранить функцийе commit.
    * Это зависит от режима сохранения $insertModeRealTime
    * 
    * @param int $posting
    */
    static public function add($posting){
        $insert = array(
            'posting'  => (int)$posting,
            'datetime' => date("Y-m-d H:i:s"),
        );    
        
        if(self::$insertModeRealTime){
            T3Db::api()->insert('buyers_channels_post_datetimes', $insert);
        }
        else{
            self::$commitArray[] = $insert;
        }                              
    }     
    
    /**
    * Сохранить накопленные данные в базу
    */
    static public function commit($end = true){
        if(count(self::$commitArray)){
            T3Db::api()->insertMulty('buyers_channels_post_datetimes', array('posting', 'datetime'), self::$commitArray);
            self::$commitArray = array();     
        }
        
        if($end) self::setInsertModeRealTime(true);    
    }
    
    static public function setInsertModeRealTime($flag){
        self::$insertModeRealTime = (bool)$flag;    
    }
    
    
    /**
    * Получить количесво лидов которые были постаны на определенный канал с определенной даты
    * Функци не оптимизированна для получения данных за большие периоды.
    * 
    * @param int $posting
    * @param string-date $datetime - Format: YYYY-MM-DD HH:MM:SS
    */
    static public function getCountPostsFromDatetime($posting, $datetime){
        return T3Db::api()->fetchOne("select count(*) from buyers_channels_post_datetimes where posting=? and datetime>?", array((int)$posting, $datetime));       
    }
    
    /**
    * Получить количество лидов отправленное баеру за последние X минут.
    * При этом не учитываются те лиды которые были отправленны с ошибками (таймауты, сервер недоступен...)
    * 
    * @param int $posting
    * @param int $lastMinutes
    */
    static public function getCountPostsFromLastMinutes($posting, $lastMinutes){
        return self::getCountPostsFromDatetime($posting, date("Y-m-d H:i:s", mktime(date("H"), date("i") - $lastMinutes, date("s"), date("m"), date("d"), date("Y"))));       
    }
    
    /**
    * Получить количесво лидов которые были постаны на определенный канал в определенный промежуток времени
    * Функци не оптимизированна для получения данных за большие периоды.
    * 
    * @param int $posting
    * @param string-date $datetimeFrom - Format: YYYY-MM-DD HH:MM:SS  
    * @param string-date $datetimeTill - Format: YYYY-MM-DD HH:MM:SS  
    */
    static public function getCountPostsBetweenDatetime($posting, $datetimeFrom, $datetimeTill){
        return T3Db::api()->fetchOne("select count(*) from buyers_channels_post_datetimes where posting=? and datetime between ? and ?", array((int)$posting, $datetimeFrom, $datetimeTill));       
    }
    
    /**
    * Получить количество лидов отправленное баеру за X минут, ну не считая последние Y минут.
    * При этом не учитываются те лиды которые были отправленны с ошибками (таймауты, сервер недоступен...)
    * 
    * @param int $posting
    * @param int $forMinutes
    * @param int $exceptLastMinutes
    */
    static public function getCountPostsBetweenMinutes($posting, $forMinutes, $exceptLastMinutes = 0){
        return self::getCountPostsBetweenDatetime(
            $posting, 
            date("Y-m-d H:i:s", mktime(date("H"), date("i") - $forMinutes - $exceptLastMinutes, date("s"), date("m"), date("d"), date("Y"))),
            date("Y-m-d H:i:s", mktime(date("H"), date("i") - $exceptLastMinutes, date("s"), date("m"), date("d"), date("Y")))
        );       
    }
}