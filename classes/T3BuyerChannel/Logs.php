<?php

class T3BuyerChannel_Logs {
    static protected $insertModeRealTime = true;
    
    static protected $commitArray = array();
    
    /** @return Zend_Db_Adapter_Abstract */
    static public function getDatabase(){
        return T3Db::logs();
    }
    
    static public function getTableNameHead($leadDate){
        return "buyer_channels_logs_head_" . substr($leadDate, 0, 4) . "_" . substr($leadDate, 5, 2);    
    }
    
    static public function getTableNameBody($leadDate){
        $part = "0";
        $day = substr($leadDate, 8, 2);
        if($day <= 10)$part = 1;
        else if($day <= 20)$part = 2;
        else if($day <= 31)$part = 3;
        
        return "buyer_channels_logs_body_" . substr($leadDate, 0, 4) . "_" . substr($leadDate, 5, 2) . "__{$part}";    
    }  
    
    static protected function createLogTable_Head($tableName){
        try {
            self::getDatabase()->query("CREATE TABLE `" . $tableName . "` (       
             `id` int(1) unsigned NOT NULL auto_increment,         
             `buyerChannelID` mediumint(1) unsigned default NULL,  
             `leadID` int(1) unsigned default NULL,                
             `startDate` datetime default NULL,                    
             `status` tinyint(1) unsigned default NULL,            
             `minPrice` decimal(6,2) unsigned default NULL,        
             `priceWM` decimal(6,2) unsigned default NULL,         
             `priceTTL` decimal(6,2) unsigned default NULL,        
             `secondsAll` float unsigned default NULL,             
             `comment` int(1) unsigned default NULL,               
             `isDuplicated` tinyint(1) default NULL,               
             `isFiltered` tinyint(1) default NULL,                 
             `isSend` tinyint(1) default NULL,                     
             `isSold` tinyint(1) default NULL,                     
             `isTest` tinyint(1) default NULL,   
             `isError` tinyint(1) default NULL,                    
             `isTimeout` tinyint(1) default NULL,                  
             PRIMARY KEY  (`id`),                                  
             KEY `leadID` (`leadID`)                               
            ) ENGINE=InnoDB");
        } catch(Exception $e){}   
    }
    
    static protected function createLogTable_Body($tableName){
        try {
            self::getDatabase()->query("CREATE TABLE `" . $tableName . "` (    
             `id` int(1) unsigned NOT NULL auto_increment,         
             `buyerChannelID` mediumint(1) unsigned default NULL,  
             `leadID` int(1) unsigned default NULL,                
             `startDate` datetime default NULL,                    
             `sendLog` blob,                                   
             PRIMARY KEY  (`id`),                                  
             KEY `leadID` (`leadID`)                               
            ) ENGINE=InnoDB");
        } catch(Exception $e){}     
    }
    
    /**
    * Добавить данные
    * Они или сразу записываются в базу или добавляются в очередь, которую в дальнейшем можно будет сохранить функцие commit.
    * Это зависит от режима сохранения $insertModeRealTime
    * 
    * @param int $posting
    */
    static public function add(T3Lead $lead, T3BuyerChannel_PostResult $result){
        $comment = "";
        if(strlen($result->errorDescription))     $comment = $result->errorDescription; 
        else if(strlen($result->rejectedComment)) $comment = $result->rejectedComment;
        
        $lead->addPostingsSended($result->buyerChannelID);
        
        if(self::$insertModeRealTime){
            // Сохраненеи в реальном времени
            $insert = array(                                               
                'buyerChannelID'    =>  $result->buyerChannelID,                                             
                'leadID'            =>  $result->leadID,                                             
                'startDate'         =>  $result->startDate,                                             
                'status'            =>  self::getStatusID($result->status),                                             
                'minPrice'          =>  $result->minPrice,                                             
                'priceWM'           =>  round($result->price, 2),                                             
                'priceTTL'          =>  round($result->priceTTL, 2),                                             
                'secondsAll'        =>  $result->secondsAll,                                             
                'comment'           =>  self::getReasonID($comment),                                             
                'isDuplicated'      =>  $result->isDuplicated(),                                             
                'isFiltered'        =>  $result->isFiltered(),                                             
                'isSend'            =>  $result->isSend(),  
                'isSold'            =>  $result->isSold(),  
                'isTest'            =>  $result->isTest(),  
                'isError'           =>  $result->isError(),  
                'isTimeout'         =>  $result->isTimeout(), 
            );
            
            try {
                self::getDatabase()->insert(self::getTableNameHead($lead->datetime), $insert);
            }
            catch(Exception $e){
                self::createLogTable_Head(self::getTableNameHead($lead->datetime));
                self::getDatabase()->insert(self::getTableNameHead($lead->datetime), $insert);     
            }
            
            if(is_array($result->sendLog) && count($result->sendLog)){
                $insert = array(
                    'buyerChannelID'    =>  $result->buyerChannelID,                                             
                    'leadID'            =>  $result->leadID,                                             
                    'startDate'         =>  $result->startDate,
                    'sendLog'           =>  new Zend_Db_Expr("COMPRESS(" . self::getDatabase()->quote(serialize($result->sendLog)) . ")"),
                );
                
                try {
                    self::getDatabase()->insert(self::getTableNameBody($lead->datetime), $insert);  
                }
                catch(Exception $e){
                    self::createLogTable_Body(self::getTableNameBody($lead->datetime));
                    self::getDatabase()->insert(self::getTableNameBody($lead->datetime), $insert);    
                }
                
            }
        }
        else{ 
            // Сохранение в очередь                                                     
            self::$commitArray[] = array(                                               
                'leadDatetime'      =>  $lead->datetime,
                'buyerChannelID'    =>  $result->buyerChannelID,                                             
                'leadID'            =>  $result->leadID,                                             
                'startDate'         =>  $result->startDate,                                             
                'status'            =>  $result->status,                                             
                'minPrice'          =>  $result->minPrice,                                             
                'priceWM'           =>  $result->price,                                             
                'priceTTL'          =>  $result->priceTTL,                                             
                'secondsAll'        =>  $result->secondsAll,                                             
                'comment'           =>  $comment,                                             
                'isDuplicated'      =>  $result->isDuplicated(),                                             
                'isFiltered'        =>  $result->isFiltered(),                                             
                'isSend'            =>  $result->isSend(),  
                'isSold'            =>  $result->isSold(),  
                'isTest'            =>  $result->isTest(),  
                'isError'           =>  $result->isError(),  
                'isTimeout'         =>  $result->isTimeout(),
                'sendLog'           =>  $result->sendLog,
            );
        }                              
    } 
    
    static protected $statusesCache = array();
    static public function getStatusID($name){
        if(!isset(self::$statusesCache[$name])){
            $id = T3Db::api()->fetchOne("select id from buyer_channels_logs__statuses where `status`=?", $name);
            if(!$id){
                T3Db::api()->insert("buyer_channels_logs__statuses", array('status' => $name));
                $id = T3Db::api()->lastInsertId();    
            }
            self::$statusesCache[$name] = $id;
        }
        return self::$statusesCache[$name];    
    }
    
    static public function getStatusName($id){
        $r = array_search($id, self::$statusesCache);
        
        if($r === false){
            $name = T3Db::api()->fetchOne("select `status` from buyer_channels_logs__statuses where `id`=?", $id);
            if($name !== false){
                self::$statusesCache[$name] = $id; 
                $r = $name;     
            }
        }
        
        return (string)$r;    
    }
    
    static public function loadAllStatuses(){
        self::$statusesCache = T3Db::api()->fetchPairs("select `status`, id from buyer_channels_logs__statuses");        
    }
    
    
    
    static protected $reasonsCache = array();
    static public function getReasonID($text){
        $hash = sprintf("%u", crc32($text));
        if(!isset(self::$reasonsCache[$hash])){      
            $is = T3Db::api()->fetchOne("select `hash` from buyer_channels_logs__comments where `hash`=? limit 1", $hash);
            if(!$is){
                try {
                    T3Db::api()->insert("buyer_channels_logs__comments", array(
                        'hash'    => $hash,
                        'comment' => $text,
                    ));  
                } catch(Exception $e){}    
            }
            self::$reasonsCache[$hash] = true;
        }
        return $hash;    
    } 
    
    /**
    * Загрузка вусез имеющихся в базе
    * Поскольку добавлене новых происходт давльно редко, оптимизация добавления бесполезна
    * 
    * @param mixed $reasons
    */
    static public function loadReasonsArray($reasons){
        if(is_array($reasons) && count($reasons)){
            $crc32_index = array();
            foreach($reasons as $el){
                $h = sprintf("%u", crc32($el));
                if(!isset(self::$reasonsCache[$h])){
                    $crc32_index[] = $h;  
                }
            }

            try {
                $base = T3Db::api()->fetchCol("select `hash` from buyer_channels_logs__comments where `hash` in (" . implode(",", array_unique($crc32_index)) . ")");
                if(count($base)){
                    foreach($base as $bh){
                        self::$reasonsCache[$bh] = true;
                    }
                }
            }
            catch(Exception $e){

            }
        }    
    } 
    
    static protected $reasonsBase = array();
    static public function loadReasonsFromIDs($array){
        if(count($array)){
            $res = T3Db::api()->fetchPairs("select `hash`, `comment` from buyer_channels_logs__comments where `hash` in (" . implode(",", array_unique($array)) . ")");
            if(count($res)){
                foreach($res as $hash => $comment){
                    self::$reasonsBase[$hash] = $comment;
                }    
            }
        }
    } 
    static public function getReasonText($hash){
        if(!isset(self::$reasonsBase[$hash])){
            self::loadReasonsFromIDs(array($hash));    
        }
        return ifset(self::$reasonsBase[$hash]);
    } 
    
    /**
    * Сохранить накопленные данные в базу
    */
    static public function commit($end = true){
        // Запись всей очереди в базу   
        if(count(self::$commitArray)){  
            // Обновление кеша для Statuses
            self::loadAllStatuses();
            
            // Обновление кеша для Comments
            $allReasons = array();
            foreach(self::$commitArray as $el){
                $allReasons[] = $el['comment'];         
            }
            self::loadReasonsArray(array_unique($allReasons));
            
            // Формирование массивов добаления данных в базу
            $insertHead = array();
            $insertBody = array();
            
            foreach(self::$commitArray as $el){
                $tableHead = self::getTableNameHead($el['leadDatetime']);
                if(!isset($insertHead[$tableHead]))$insertHead[$tableHead] = array();
                $insertHead[$tableHead][] = array(
                    'buyerChannelID'    =>  $el['buyerChannelID'],                                             
                    'leadID'            =>  $el['leadID'],                                             
                    'startDate'         =>  $el['startDate'],                                             
                    'status'            =>  self::getStatusID($el['status']),                                             
                    'minPrice'          =>  $el['minPrice'],                                             
                    'priceWM'           =>  round($el['priceWM'], 2),                                             
                    'priceTTL'          =>  round($el['priceTTL'], 2),                                             
                    'secondsAll'        =>  $el['secondsAll'],                                             
                    'comment'           =>  self::getReasonID($el['comment']),                                             
                    'isDuplicated'      =>  $el['isDuplicated'],                                             
                    'isFiltered'        =>  $el['isFiltered'],                                             
                    'isSend'            =>  $el['isSend'],  
                    'isSold'            =>  $el['isSold'],  
                    'isTest'            =>  $el['isTest'],  
                    'isError'           =>  $el['isError'],  
                    'isTimeout'         =>  $el['isTimeout'], 
                );
                
                if(is_array($el['sendLog']) && count($el['sendLog'])){ 
                    $tableBody = self::getTableNameBody($el['leadDatetime']);
                    if(!isset($insertBody[$tableBody]))$insertBody[$tableBody] = array();
                    $insertBody[$tableBody][] = array(
                        'buyerChannelID'    =>  $el['buyerChannelID'],                                             
                        'leadID'            =>  $el['leadID'],                                             
                        'startDate'         =>  $el['startDate'],
                        'sendLog'           =>  new Zend_Db_Expr("COMPRESS(" . T3Db::api()->quote(serialize($el['sendLog'])) . ")"), 
                    );    
                }   
            }
            
            // Если есть хотябы одна табличка для обнвлений, Записать все значения Head
            if(count($insertHead)){           
                foreach($insertHead as $table => $data){  
                    try {
                        self::getDatabase()->insertMulty($table, array_keys($data[0]), $data);  
                    }
                    catch(Exception $e){
                        self::createLogTable_Head($table);
                        self::getDatabase()->insertMulty($table, array_keys($data[0]), $data);       
                    }
                }
            }
            
            // Если есть хотябы одна табличка для обнвлений, Записать все значения Body
            if(count($insertBody)){
                foreach($insertBody as $table => $data){
                    try {
                        self::getDatabase()->insertMulty($table, array_keys($data[0]), $data);  
                    }
                    catch(Exception $e){
                        self::createLogTable_Body($table);
                        self::getDatabase()->insertMulty($table, array_keys($data[0]), $data);      
                    }
                }  
            }
            
            self::$commitArray = array();
        }
        
        if($end) self::setInsertModeRealTime(true);
    }
    
    static public function setInsertModeRealTime($flag){
        self::$insertModeRealTime = (bool)$flag;    
    }
    
    static public function getAllLogs(T3Lead $lead, $posting = null){
        $result = array();
        if($lead->id != 999999){
            $postingAdd = $posting ? " AND buyerChannelID='" . (int)$posting . "'" : "";

            try{
                $result = self::getDatabase()->fetchAll("select * from `" . self::getTableNameHead($lead->datetime) . "` where leadID=? {$postingAdd} limit 1000", $lead->id);
            }
            catch(Exception $e){
                $result = array();
            }


            if (count($result) == 0){
                try{
                    $result = T3Db::logstemp()->fetchAll("select * from `" . self::getTableNameHead($lead->datetime) . "` where leadID=? {$postingAdd} limit 1000", $lead->id);
//                    varExport($result);
                }
                catch(Exception $e){
                    $result = array();
//                    varExport($e->getMessage());
                }
            }

            if(count($result)){
                // На случай если был сделан допустим офильрованный лид, то заголовки будут а таблица тела может быть еще не созданна
                try{             
                    $body = self::getDatabase()->fetchAll(
                        "select buyerChannelID, uncompress(sendLog) as l  from `" . self::getTableNameBody($lead->datetime) . "` where leadID=? {$postingAdd} limit 1000", $lead->id
                    ); 
                }
                catch(Exception $e){
                    $body = array();    
                }



                if (count($body) == 0){
                    try{
                        $body = T3Db::logstemp()->fetchAll("select buyerChannelID, uncompress(sendLog) as l  from `" . self::getTableNameBody($lead->datetime) . "` where leadID=? {$postingAdd} limit 1000", $lead->id);
                    }
                    catch(Exception $e){

                        varExport($e->getMessage());
                        $body = array();
                    }
                }


                
                $comments = array();
                foreach($result as $v){
                    $comments[$v['comment']] = true;    
                }
                
                self::loadReasonsFromIDs(array_keys($comments));
                                    
                foreach($result as $k => $v){
                    $result[$k]['status']  = self::getStatusName($v['status']);
                    $result[$k]['comment'] = self::getReasonText($v['comment']);
                    
                    if($v['isSend']){  
                        $result[$k]['sendLog'] = array();
                        if(count($body)){
                            foreach($body as $bk => $bv){
                                if($bv['buyerChannelID'] == $v['buyerChannelID']){
                                    $result[$k]['sendLog'] = unserialize($bv['l']);
                                    unset($body[$bk]);  
                                    break;   
                                } 
                            } 
                        }   
                    }
                }       
            }
        } 
        
        return $result;  
    }
    
    static public function getAllHeads(T3Lead $lead, $colums = array(), $oneCol = false){
        $result = array();
        if($lead->id && $lead->id != 999999){  
            if(count($colums)){
                $colums = "`" . implode("`,`", $colums) . "`";
            }
            else {
                $colums = "*";    
            }
            
            if($oneCol){
                $result = self::getDatabase()->fetchCol(
                    "select {$colums} from `" . T3BuyerChannel_Logs::getTableNameHead($lead->datetime) . "` where leadID=?", 
                    $lead->id
                );    
            }
            else {
                $result = self::getDatabase()->fetchAll(
                    "select {$colums} from `" . T3BuyerChannel_Logs::getTableNameHead($lead->datetime) . "` where leadID=?", 
                    $lead->id
                );    
            }             
        }
        return $result; 
    }
}
