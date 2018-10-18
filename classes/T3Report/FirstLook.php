<?php

/*

cache_first_look_daily  CREATE TABLE `cache_first_look_daily` (                           
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,                  
  
                                              Ключи:
  `date` date NOT NULL,                                Дата           
  `webmaster` int(11) NOT NULL,                        Вебмастр           
  `posting` int(11) NOT NULL,                          FL постинг           
  
                                              Кеши:
  `leads_all` int(11) DEFAULT '0',                     Сколько лидов пробовало продаться на FL постинг (Если он на паузе, то лиды не считаются)          
  `leads_filter` int(11) DEFAULT '0',                  Сколько лидов было отфильтрованно FL постингом           
  `leads_sold` int(11) DEFAULT '0',                    Сколько лидов было проданно на FL постинг           
  `leads_nointerest` int(11) DEFAULT '0',              Сколько лидов FL канал купил, ну они ему были не интересны и пошли на перепродажу            
  `leads_resold` int(11) DEFAULT '0',                  Сколько лидов было перепроданно на других баеров          
  `wm_sold` decimal(10,2) DEFAULT '0.00',              Сколько получил вебмастр от продаж на FL канал           
  `wm_resold` decimal(10,2) DEFAULT '0.00',            Сколько получил вебмастр за перепроданные лиды           
  `buyer_resold` decimal(10,2) DEFAULT '0.00',         Сколько T3 долно FL постингу за перепроданные лиды           
  `ttl_sold` decimal(10,2) DEFAULT '0.00',             Сколько должен за лиды FL постинг           
  `ttl_resold` decimal(10,2) DEFAULT '0.00',           За сколько продались лиды на других баеров после того как они были не интересны           
  
  PRIMARY KEY (`id`),                                             
  UNIQUE KEY `main` (`date`,`webmaster`,`posting`)                
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci  
* 
*/

class T3Report_FirstLook { 
    static protected $pool = array();
    static protected $webmaster;
    static protected $posting;
    
    /**
    * Установить вебмастра
    * 
    * @param mixed $webmaster
    */
    static public function setWebmaster($webmaster){
        self::$webmaster = $webmaster;
    }
    
    /**
    * Установить FL постинг
    * 
    * @param mixed $posting
    */
    static public function setPosting($posting){
        self::$posting = $posting;
    }
    
    /*****************************************************************************************************************************/
    
    /**
    * Абстрактная функция для добавления данных в пул
    * 
    * @param mixed $webmaster
    * @param mixed $posting
    * @param mixed $data
    */
    static protected function addAbstract($data){
        if(count($data)){
            if(!isset(self::$pool[self::$webmaster][self::$posting])){
                self::$pool[self::$webmaster][self::$posting] = array();   
            }    
         
            foreach($data as $k => $v){
                if(!isset(self::$pool[self::$webmaster][self::$posting][$v])){
                    self::$pool[self::$webmaster][self::$posting][$k] = $v;   
                }    
                else {
                    self::$pool[self::$webmaster][self::$posting][$k]+= $v;    
                }
            }
        }
    }
    
    /*****************************************************************************************************************************/
    
    /**
    * Добавить лид, который прошел через FirstLook на этоу связку вебмастра и постинг
    */
    static public function addLeadAll(){
        self::addAbstract(array(
            'leads_all' => 1,
        ));    
    }
    
    /**
    * Добавить отфильтованный лид
    */
    static public function addLeadFiltered(){
        self::addAbstract(array(
            'leads_filter' => 1,
        ));    
    }
    
    /**
    * Добавить проданный на FL канал лид
    */
    static public function addLeadSold($wm, $ttl){
        self::addAbstract(array(
            'leads_sold'    => 1,
            'wm_sold'       => $wm,
            'ttl_sold'      => $ttl,
        ));    
    }
    
    /**
    * Добавить информацию о том что баера не интересует лид, который он купил
    */
    static public function addLeadNoInterest(){
        self::addAbstract(array(
            'leads_nointerest' => 1,  
        ));    
    }
    
    static public function addLeadResold($buyer, $wm, $ttl){
        self::addAbstract(array(
            'leads_resold'  => 1,  
            'buyer_resold'  => $buyer,  
            'wm_resold'     => $wm,  
            'ttl_resold'    => $ttl,  
        ));    
    }
    
    /*****************************************************************************************************************************/
       
    /**
    * Записать вседанные из пула в базу
    */
    static public function commit(){
        if(count(self::$pool)){
            foreach(self::$pool as $webmaster => $el){
                if(count($el)){
                    foreach($el as $posting => $data){
                        if(count($data)){
                            $update = array();
                            
                            foreach($data as $k => $v){
                                if($v != 0){
                                    $update[$k] = $v; 
                                    self::$pool[$webmaster][$posting][$v] = 0; 
                                }
                            } 
                            
                            if(count($update)){
                                // проверить, есть ли в кешах запись на данные ключи
                                $is = (bool)T3Db::api()->fetchOne(
                                    "select id from cache_first_look_daily where " . 
                                    "`date`='" . date('Y-m-d') . "' and webmaster={$webmaster} and posting={$posting}"
                                );
                                
                                if($is){
                                    // уже есть запись
                                    try {
                                        $updateArray = array();
                                        foreach($update as $updateK => $updateV){
                                            $updateArray[$updateK] = new Zend_Db_Expr("`{$updateK}`+{$updateV}");    
                                        }
                                        
                                        T3Db::api()->update(
                                            "cache_first_look_daily", 
                                            $updateArray, 
                                            "`date`='" . date('Y-m-d') . "' and webmaster={$webmaster} and posting={$posting}"
                                        );     
                                    }
                                    catch(Exception $e){
                                        
                                    }
                                }
                                else {
                                    // запись нет
                                    try {
                                        T3Db::api()->insert("cache_first_look_daily", array(
                                            'date'      => date('Y-m-d'),
                                            'webmaster' => $webmaster,
                                            'posting'   => $posting,
                                        ) + $update); 
                                    }
                                    catch(Exception $e){
                                        // запись появилась между проверкой и добавлением новой
                                        try {
                                            $updateArray = array();
                                            foreach($update as $updateK => $updateV){
                                                $updateArray[$updateK] = new Zend_Db_Expr("`{$updateK}`+{$updateV}");    
                                            }
                                            
                                            T3Db::api()->update(
                                                "cache_first_look_daily", 
                                                $updateArray, 
                                                "`date`='" . date('Y-m-d') . "' and webmaster={$webmaster} and posting={$posting}"
                                            );                   
                                        }
                                        catch(Exception $e){
                                            
                                        }
                                    }
                                }
                            }  
                        }
                    }
                }
            }
        }    
    }
}  