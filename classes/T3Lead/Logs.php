<?php

class T3Lead_Logs {
    /** @return Zend_Db_Adapter_Abstract */
    static public function getDatabase(){
        return T3Db::logs();
    }
    
    static public function getTableName($leadDate){
        $part = "0";
        $day = substr($leadDate, 8, 2);
        if($day <= 10)$part = 1;
        else if($day <= 20)$part = 2;
        else if($day <= 31)$part = 3;
        
        return "channels_newlead_log_" . substr($leadDate, 0, 4) . "_" . substr($leadDate, 5, 2) . "__{$part}";    
    }
    
    static protected function createLogTable($tableName){
        try {
            self::getDatabase()->query("CREATE TABLE `{$tableName}` (                                                                               
                `id` int(1) unsigned NOT NULL AUTO_INCREMENT,                                                                     
                `create_date` datetime DEFAULT NULL,                                                                              
                `status` enum('process','error','sold','reject','duplicate','quality_verification','pending') DEFAULT 'process',  
                `descriptions` text,                                                                                              
                `seconds` float DEFAULT '0',                                                                                      
                `secondsAuth` float DEFAULT '0',                                                                                  
                `secondsCreateLead` float DEFAULT '0',                                                                            
                `secondsHeader` float DEFAULT '0',                                                                                
                `secondsBody` float DEFAULT '0',                                                                                  
                `secondsFroudDetector` float DEFAULT '0',                                                                         
                `secondsPost` float DEFAULT '0',                                                                                  
                `request` text,                                                                                                   
                `response` text,                                                                                                  
                `channelType` enum('js_form','post_channel') DEFAULT NULL,                                                        
                `channelID` int(1) DEFAULT NULL,                                                                                  
                `webmasterID` int(1) DEFAULT NULL,                                                                                
                `leadProduct` varchar(64) DEFAULT NULL,                                                                           
                `testMode` enum('0','1') DEFAULT NULL,                                                                            
                `leadID` int(1) DEFAULT NULL,                                                                                     
                `clientIP` varchar(15) DEFAULT NULL,                                                                              
                `minPrice` decimal(10,2) DEFAULT '0.00',                                                                          
                `subAccount` varchar(255) DEFAULT NULL,                                                                           
                `subAccountID` int(1) DEFAULT NULL,                                                                               
                `firstReferer` mediumtext,                                                                                        
                PRIMARY KEY (`id`),                                                                                               
                KEY `leadID` (`leadID`)                                                                                           
          ) ENGINE=InnoDB");
        } catch(Exception $e){}   
    }
    
    static public function insert($leadDate, $params){
        $tableName = self::getTableName($leadDate); 
        

        $params['descriptions'] = serialize($params['descriptions']);
        
        try {
            self::getDatabase()->insert($tableName, $params);
        }   
        catch(Exception $e){
            self::createLogTable($tableName);
            self::getDatabase()->insert($tableName, $params);    
        } 
    }
    
    static public function getLogArray($lead){
        if(!($lead instanceof T3Lead)){
            $leadID = (int)$lead; 
            $lead = new T3Lead();
            $lead->fromDatabase($leadID);
        }
        
        if($lead->id){
            $result = array();
            try{
                $result = self::getDatabase()->fetchAll('select * from `' . self::getTableName($lead->datetime) . '` where leadID=?', $lead->id);
            }
            catch(Exception $e){
                $result = array();
            }

            if (count($result) == 0){
                try{
                    $result = T3DB::logstemp()->fetchAll('select * from `' . self::getTableName($lead->datetime) . '` where leadID=?', $lead->id);

                }catch(Exception $e){
                    $result = array();
                }
            }
            return $result;
        }
        return array();
    }

}