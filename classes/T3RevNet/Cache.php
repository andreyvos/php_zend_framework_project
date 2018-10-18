<?php

class T3RevNet_Cache {
    static public function addPost(T3RevNet_Lead $lead){
        $date = substr($lead->create_date, 0, 10);
        
        try{
            T3Db::api()->insert("revnet_cache_all", array(
                'date'      =>  $date,
                'account'   =>  $lead->account,
                'post'      =>  1,
            ));
        }
        catch(Exception $e){
            T3Db::api()->update("revnet_cache_all", array(
                'post'      =>  new Zend_Db_Expr("`post`+1"),
            ), "`date`=" . T3Db::api()->quote($date) . " and `account`=" . T3Db::api()->quote($lead->account));           
        }
        
        try{
            T3Db::api()->insert("revnet_cache_detail", array(
                'date'      =>  $date,
                'webmaster' =>  $lead->webmaster,
                'account'   =>  $lead->account,
                'post'      =>  1,
            ));
        }
        catch(Exception $e){
            T3Db::api()->update("revnet_cache_detail", array(
                'post'      =>  new Zend_Db_Expr("`post`+1"),
            ), "`date`=" . T3Db::api()->quote($date) . " and `account`=" . T3Db::api()->quote($lead->account) . " and `webmaster`=" . T3Db::api()->quote($lead->webmaster));           
        }
    }
    
    
    static public function soldLead(T3RevNet_Lead $lead, $countSold, $rev, $wm, $ttl, $getPrice){
        // кеш треков
        $date = date('Y-m-d');
        
        try{
            T3Db::api()->insert("revnet_cache_all", array(
                'date'          =>  $date,
                'account'       =>  $lead->account,
                'tracks'        =>  1,
                'get_revnet'    =>  $getPrice,
            ));
        }
        catch(Exception $e){
            T3Db::api()->update("revnet_cache_all", array(
                'tracks'        =>  new Zend_Db_Expr("`tracks`+1"), 
                'get_revnet'    =>  new Zend_Db_Expr("`get_revnet`+{$getPrice}"), 
            ), "`date`=" . T3Db::api()->quote($date) . " and `account`=" . T3Db::api()->quote($lead->account));           
        }
        
        try{
            T3Db::api()->insert("revnet_cache_detail", array(
                'date'          =>  $date,
                'webmaster'     =>  $lead->webmaster,
                'account'       =>  $lead->account,
                'tracks'        =>  1,
                'get_revnet'    =>  $getPrice,
            ));
        }
        catch(Exception $e){
            T3Db::api()->update("revnet_cache_detail", array(
                'tracks'        =>  new Zend_Db_Expr("`tracks`+1"),
                'get_revnet'    =>  new Zend_Db_Expr("`get_revnet`+{$getPrice}"),
            ), "`date`=" . T3Db::api()->quote($date) . " and `account`=" . T3Db::api()->quote($lead->account) . " and `webmaster`=" . T3Db::api()->quote($lead->webmaster));           
        }
        
        // кеш лидов 
        $date = substr($lead->create_date, 0, 10); 
        
        try{
            T3Db::api()->insert("revnet_cache_all", array(
                'date'          =>  $date,
                'account'       =>  $lead->account,
                'sold'          =>  $countSold,
                'amt_revnet'    =>  $rev,
                'amt_webmaster' =>  $wm,
                'amt_total'     =>  $ttl,
            ));
        }
        catch(Exception $e){
            T3Db::api()->update("revnet_cache_all", array(
                'sold'          =>  new Zend_Db_Expr("`sold`+{$countSold}"), 
                'amt_revnet'    =>  new Zend_Db_Expr("`amt_revnet`+{$rev}"), 
                'amt_webmaster' =>  new Zend_Db_Expr("`amt_webmaster`+{$wm}"), 
                'amt_total'     =>  new Zend_Db_Expr("`amt_total`+{$ttl}"), 
            ), "`date`=" . T3Db::api()->quote($date) . " and `account`=" . T3Db::api()->quote($lead->account));           
        }
        
        try{
            T3Db::api()->insert("revnet_cache_detail", array(
                'date'          =>  $date,
                'webmaster'     =>  $lead->webmaster,
                'account'       =>  $lead->account,
                'sold'          =>  $countSold,
                'amt_revnet'    =>  $rev,
                'amt_webmaster' =>  $wm,
                'amt_total'     =>  $ttl,
            ));
        }
        catch(Exception $e){
            T3Db::api()->update("revnet_cache_detail", array(
                'sold'          =>  new Zend_Db_Expr("`sold`+{$countSold}"), 
                'amt_revnet'    =>  new Zend_Db_Expr("`amt_revnet`+{$rev}"), 
                'amt_webmaster' =>  new Zend_Db_Expr("`amt_webmaster`+{$wm}"), 
                'amt_total'     =>  new Zend_Db_Expr("`amt_total`+{$ttl}"), 
            ), "`date`=" . T3Db::api()->quote($date) . " and `account`=" . T3Db::api()->quote($lead->account) . " and `webmaster`=" . T3Db::api()->quote($lead->webmaster));           
        }       
    }
   
    
    static protected function sumColumsArray($colums){
        $tempColums = array();
        foreach($colums as $el){
            if(in_array($el, array('date'))){
                $tempColums[] = $el;
            }
            else {
                $tempColums[] = new Zend_Db_Expr("sum(`{$el}`) as `{$el}`");    
            }    
        }
        
        return $tempColums;            
    }
    
    
    static public $columsMainReport = array('date', 'post', 'sold', 'tracks', 'amt_revnet', 'amt_webmaster', 'amt_total', 'get_revnet'); 
    
    /**
    * Получение массива данных из основного кеша
    * 
    * @param T3Report_Header $header
    * @param bool $onlyTotal
    */
    static public function getMainCacheData(T3RevNet_Header $header, $onlyTotal = false){
        $mysql_table = ""; # таблица индексов, по которой будет проходить отбор
        
        /** @var Zend_Db_Select */
        $select = T3Db::api()->select();
        
        // фильтрация по дате
        $select->where("`date` BETWEEN '{$header->dateFrom}' AND '{$header->dateTill}'");
        
        // выбираем объект для выборок
        if($header->webmasterID){
            $mysql_table = "revnet_cache_detail"; 
            $select->where("`webmaster`=?", $header->webmasterID); // для определенного вебмастера    
        } 
        else {
            $mysql_table = "revnet_cache_all";     
        }
        
        if(!is_null($header->account))      $select->where("`account`=?", $header->account);     // Продукт  

        if(!is_null($header->product) && is_null($header->account)){
            if ($header->product == 'payday'){
                $select->where("`account` in ('soldHigh','soldLow','notSold','serverPost','notSoldOnlyPhone','notSoldDup','notSoldTime')");
            }
            if ($header->product == 'ukpayday'){
                $select->where("`account` in ('UKPaydayServerPost','UKPaydayLeads','UKPaydayNotSoldDup','UKPaydayNotSoldTime','UKPaydayNotSold')");
            }
            if ($header->product == 'capayday'){
                $select->where("`account` in ('CanadaLeads','CanadaNotSold','CanadaNotSoldDup','CanadaNotSoldTime','CanadaServerPost')");
            }
            if ($header->product == 'personalloan'){
                $select->where("`account` in ('UsaPersonalLoanLeads','UsaPersonalLoanNotSold','UsaPersonalLoanNotSoldDup','UsaPersonalLoanNotSoldTime','UsaPersonalLoanServerPost')");
            }
        }

        $select->from($mysql_table, self::sumColumsArray(self::$columsMainReport));
        if(!$onlyTotal)$select->group("date"); 
        
        
        if($onlyTotal){
            // только суммы
            $data = T3Db::api()->fetchRow($select);
            unset($data['date']);
            self::addConvertFields($data); 
            
            return $data;     
        }
        else {
            
            // детальныйе данные
            $data = T3Db::api()->fetchAll($select);
            
            $index = array();
            foreach($data as $el){
                $index[$el['date']] = $el;    
            }   
            
            
            $start_deltaDays = round(($header->dateFrom_ts - T3Report_Header::getTodayTS())/86400);
            $end_deltaDays   = round(($header->dateTill_ts - T3Report_Header::getTodayTS())/86400);
            
            $total = array();
            $result = array();
            
            $currentYear = date('Y');
            
            for($i = $end_deltaDays; $i >= $start_deltaDays; $i--){
                $mktime     =   mktime(0, 0, 0, date('m'), date("d")+$i, date("Y"));
                $dateYmd    =   date("Y-m-d", $mktime);
                $weekday    =   date('w', $mktime);
                //$dateStr    =   DateFormat::dateOnly($dateYmd, false);
                
                if($currentYear == substr($dateYmd, 0, 4))  $dateStr    = date('d M', $mktime);
                else                                        $dateStr    = date('d M, Y', $mktime);     
                
                
                $add = array(
                    'dateStr' => $dateStr, 
                    'dateStrSelWeekend' => $dateStr . (in_array($weekday, array(0,6)) ? ' *' : ''),  
                    'dateYmd' => $dateYmd,
                    'flortTime' => ((strtotime($dateYmd . " UTC"))*1000), 
                    'weekday'  => $weekday, 
                ); 
                
                foreach(self::$columsMainReport as $col){
                    if($col != 'date'){
                        $add[$col] = ifset($index[$dateYmd][$col], 0)+0;
                        $total[$col] = ifset($total[$col], 0) + $add[$col];    
                    }    
                }
                
                self::addConvertFields($add);
                $result[] = $add; 
                
            }
            
            self::addConvertFields($total);
            
            return array(
                'data' => $result,
                'total'  => $total,
            ); 
        } 
    }
    
    /**
    * Преобразование массива стрчки данных статистики
    * Убирает поля которые недоступны из за прав доступа
    * 
    * @param float $el
    */
    static protected function addConvertFields(&$el){
        $el['post'] +=0;
        $el['sold'] +=0;
        $el['tracks'] +=0;
        $el['amt_revnet'] +=0;
        $el['amt_webmaster'] +=0;
        $el['amt_total'] +=0;
        $el['get_revnet'] +=0; 
        
        $user =& T3Users::getInstance()->getCurrentUser();  
        
        if(!$user->isRoleAdmin()){
            unset($el['amt_revnet']); 
            unset($el['amt_webmaster']);
            unset($el['amt_total']);       
        }
    }   
}