<?php

class T3DoNotPresent {
    /**
    * ТЕКУЩАЯ ФУНКЦИЯ, ОНА СТАНЕТ НЕ АКТУАЛЬНА ПОСЛЕ ВЫХОДА НОВОЙ ВЕРСИИ
    */
    static public function IsNotPresent($ssn,$email,$buyer){
        
        if ($buyer == 'onlysa_hash'){
            return (bool)T3Db::api()->fetchOne("select id from dnpl_data_11 where ssn=? limit 1", array($ssn));     
        }else if ($buyer == 'onlygecc_hash'){
            return (bool)T3Db::api()->fetchOne("select id from dnpl_data_12 where ssn=? limit 1", array($ssn));     
        }else if ($buyer == 'gecc_hash'){
            return (bool)T3Db::api()->fetchOne("select id from dnpl_data_9 where ssn=? limit 1", array($ssn));     
        }else if ($buyer == 12){
            return (bool)T3Db::api()->fetchOne("select id from dnpl_data_5 where ssn=? limit 1", array($ssn));    
        }else if ($buyer == 16){
            return (bool)T3Db::api()->fetchOne("select id from dnpl_data_17 where ssn=? limit 1", array($ssn));    
        }else{        
            return false;
        }
    }
    
    static public function MEMIsNotPresent($dob,$fname,$lname,$email){
        $email_array = explode('@',$email);
        $email_search = strtolower($email_array[0]);
        $fname_search = strtolower(substr($fname,0,3));
        $lname_search = strtolower(substr($lname,0,3));
        $return = false;
        try{
            $return = (bool)T3Db::api()->fetchOne("select id from dnpl_data_20 where first_name_part=? and last_name_part=? and email_part=? and dob=? limit 1", array($fname_search, $lname_search,$email_search,$dob));
        }
        catch(Exception $e){}
        
        return $return;    
    }
    
    static public function MEMIsGoodCustomer($dob,$fname,$lname,$email){
        $email_array = explode('@',$email);
        $email_search = strtolower($email_array[0]);
        $fname_search = strtolower(substr($fname,0,3));
        $lname_search = strtolower(substr($lname,0,3));
        $return = false;
        try{
            $return = (bool)T3Db::api()->fetchOne("select id from dpl_data_1 where first_name_part=? and last_name_part=? and email_part=? and dob=? limit 1", array($fname_search, $lname_search,$email_search,$dob));
        }
        catch(Exception $e){}
        
        return $return;    
    }
    
    /**********************************************************************************************************************************************************/
    
    // Все возможные типы DNPL 
    const TYPE_SSN_EMAIL_PARTS  = 1; // часть мыла, до собачки + последние 4 чиселки SSN
    const TYPE_FULL_SSN         = 2; // полный SSN
    const TYPE_FULL_EMAIL       = 3; // полный Email
    const TYPE_MD5_SSN          = 4; // полный Email
    const TYPE_MD5_SSNDOPRESENT = 5; // md5(ssn), dopresent
    const TYPE_SHA1_SSNDOPRESENT = 6;
    
    static protected $typesLabels = array(
        self::TYPE_SSN_EMAIL_PARTS      => 'SSN+Email Parts',
        self::TYPE_FULL_SSN             => 'Full SSN',
        self::TYPE_FULL_EMAIL           => 'Full Email',
        self::TYPE_MD5_SSN              => 'MD5(SSN)',
        self::TYPE_MD5_SSNDOPRESENT     => 'MD5(SSN), Do Present',
        self::TYPE_SHA1_SSNDOPRESENT     => 'SHA1(SSN), Do Present'
    );
    
    /**
    * Конект к базе
    * В перспективе будет легко перенести в отдельную базу дагнных
    * 
    * @return Zend_Db_Adapter_Abstract 
    */
    static public function getDatabase(){
        return T3Db::api();
    }
    
    static public function getTypeTitle($type){
        return isset(self::$typesLabels[$type]) ? self::$typesLabels[$type] : "Unknown";
    }
    
    static public function getAllTypesForSelectBox($first = null){
        $all = self::$typesLabels;
        if(strlen($first)) $all = array('' => $first) + $all;
        return $all;   
    }
    
    static protected $types = null;
    /**
    * Получить массив всех DNPL листов
    */
    static public function getTypes(){
        if(self::$types === null){
            $all = self::getDatabase()->fetchAll("select * from dnpl_types where `active`=1");
            foreach($all as $el){
                self::$types[$el['id']] = $el;
            }
        }
        return self::$types;
    }
    
    /**
    * Получить какую лидо информацию определенного листа
    * 
    * Возможные занчения переменной data:
    * id, title, login, pass, type
    * В случае если листа с требуемым ID не существует или в поле data будет переданно неправильное значение, функция вернет NULL.
    * 
    * @param mixed $id
    * @param mixed $data
    * @return mixed
    */
    static public function getTypeData($id, $data){
        $types = self::getTypes();
        return isset($types[$id][$data]) ? $types[$id][$data] : null;
    }
    
    /**
    * Получить ID канала из логина и пароля
    * 
    * @param mixed $login
    * @param mixed $pass
    * @return string
    */
    static public function getListID_fromLoginAndPass($login, $pass){
        if(strlen($login) && strlen($pass)){
            try{
                return self::getDatabase()->fetchOne("select id from dnpl_types where `login`=? and `pass`=? and `active`=1", array(
                    $login,
                    $pass    
                ));
            }
            catch(Exception $e){}
        }
        return false;
    }
    
    /**
    * Отдает число, полученное из последних 4 цифр SSN
    * Если SSN не 9 знаковый, то считается что несколько его первых символов обрезано
    * 
    * Пример:
    * 123456789 -> 6789
    * 123400011 -> 11
    * 
    * 123456    -> 3456
    * 1234      -> 1234
    * 12        -> 12
    * 
    * @param mixed $ssn
    * @return mixed
    */
    static public function getSSNPart($ssn){
        return (int)substr($ssn, strlen($ssn)-4, 4);
    }
    
    /**
    * Получить ту часть мыла, которая идет до собачки, если в переданной строке нет собачки, то считается что мыло уже было преобразованно
    * 
    * @param mixed $email
    * @return mixed
    */
    static public function getEmailPart($email){
        $a = explode("@", $email);
        return trim(strtolower($a[0]));
    }
    
    /**
    * Получить CRC32 хеш для части мыла, используется в индексах поиска
    * 
    * @param mixed $email
    * @return string
    */
    static public function getEmailPartCRC32($email){
        return sprintf("%u", crc32(self::getEmailPart($email)));
    }
    
    /**
    * Получить интовую чексумму для части мыла, используется для двойной тчности.
    * 
    * @param mixed $email
    * @return string
    */
    static public function getEmailPartChecksum($email){
        return sprintf("%u", crc32("CS" . self::getEmailPart($email)));
    }
    
    /**
    * Получить CRC32 хеш для мыла, используется в индексах поиска
    * 
    * @param mixed $email
    * @return string
    */
    static public function getEmailCRC32($email){
        return sprintf("%u", crc32(trim(strtolower($email))));
    }
    
    /**
    * Получить интовую чексумму для мыла, используется для двойной тчности.
    * 
    * @param mixed $email
    * @return string
    */
    static public function getEmailChecksum($email){
        return sprintf("%u", crc32("CS" . trim(strtolower($email))));
    }
    
    /**
    * Добавить новую запись
    * 
    * @param mixed $id
    * @param mixed $ssn
    * @param mixed $email
    * @param mixed $actualDays
    */
    static public function addDNPItem($id, $ssn, $email, $actualDays = null){
        $result = array(
            'success' => 1,
            'reason'  => '',
        );
        
        if(self::getTypeData($id, 'id')){
            if($actualDays === null)    $actualDays = 60;  // по умолчанию хранить 2 месяца
            else if($actualDays < 1)    $actualDays = 1;   // ну не меньше 1 дня
            else if($actualDays > 365)  $actualDays = 365; // и не больше 1 года
            
            $actual = date('Y-m-d H:i:s', time() + (86400*($actualDays+1))); // на всякий случай прибавим еще 1 день                                                                     
            
            
            if(self::getTypeData($id, 'type') == self::TYPE_MD5_SSN || self::getTypeData($id, 'type') == self::TYPE_MD5_SSNDOPRESENT){
                if(strlen($ssn)==32){
                    try{
                        self::getDatabase()->insert("dnpl_data_{$id}", array(
                            'ssn'                   => $ssn,
                            'actual'                => $actual,
                        )); 
                    }
                    catch(Exception $e){
                        try {
                            self::getDatabase()->query("
                                CREATE TABLE `dnpl_data_{$id}` (                            
                                   `id` int(1) unsigned NOT NULL auto_increment,         
                                   `ssn` varchar(32) default NULL,            
                                   `actual` date default NULL,                           
                                   PRIMARY KEY  (`id`),                                  
                                   KEY `main` (`ssn`),           
                                   KEY `actual` (`actual`)                               
                                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8  
                            "); 
                            
                            self::getDatabase()->insert("dnpl_data_{$id}", array(
                                'ssn'                   => $ssn,
                                'actual'                => $actual,
                            ));   
                        }
                        catch(Exception $e){
                            // совсем все плохо
                            $result['success'] = 0;
                            $result['reason']  = 'Unknown Error #1'; 
                        }    
                    }
                }    
            }else if(self::getTypeData($id, 'type') == self::TYPE_SSN_EMAIL_PARTS){
                if(strlen($ssn) && strlen($email)){
                    try{
                        self::getDatabase()->insert("dnpl_data_{$id}", array(
                            'ssn_part'              => self::getSSNPart($ssn),
                            'email_part_crc32'      => self::getEmailPartCRC32($email),   
                            'email_part_checksum'   => self::getEmailPartChecksum($email),
                            'actual'                => $actual,
                        )); 
                    }
                    catch(Exception $e){
                        try {
                            self::getDatabase()->query("
                                CREATE TABLE `dnpl_data_{$id}` (                            
                                   `id` int(1) unsigned NOT NULL auto_increment,         
                                   `ssn_part` smallint(1) unsigned default NULL,         
                                   `email_part_crc32` int(1) unsigned default NULL,      
                                   `email_part_checksum` int(1) unsigned default NULL,   
                                   `actual` date default NULL,                           
                                   PRIMARY KEY  (`id`),                                  
                                   KEY `main` (`ssn_part`,`email_part_crc32`),           
                                   KEY `actual` (`actual`)                               
                                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8  
                            "); 
                            
                            self::getDatabase()->insert("dnpl_data_{$id}", array(
                                'ssn_part'              => self::getSSNPart($ssn),
                                'email_part_crc32'      => self::getEmailPartCRC32($email),   
                                'email_part_checksum'   => self::getEmailPartChecksum($email),
                                'actual'                => $actual,
                            ));   
                        }
                        catch(Exception $e){
                            // совсем все плохо
                            $result['success'] = 0;
                            $result['reason']  = 'Unknown Error #1'; 
                        }
                    }
                }
                else {
                    $result['success'] = 0;
                    $result['reason']  = 'Invalid data'; 
                }      
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'This DNPL does not support adding records for this interface'; 
            }  
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Invalid credentials'; 
        }
        
        return $result;
    }
    
    static public function addDNPItemForWeb($login, $password, $ssn, $email, $actualDays = null){
        $result = array(
            'success' => 1,
            'reason'  => '',
        );
        
        $listID = self::getListID_fromLoginAndPass($login, $password);
        
        if($listID){
            $result = self::addDNPItem($listID, $ssn, $email, $actualDays);
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Invalid credentials';   
        }
        
        return $result;
    }
    
    /**
    * Отчистить лист от старых записей
    * 
    * @param mixed $id
    */
    static public function clearList($id){
        if(self::getTypeData($id, 'id')){
            $deleteLimit = 100000;
            $rowsCount = 0;
            
            self::getDatabase()->insert("dnpl_clear_log", array(
                'dnpl'  => $id,
                'start' => date('Y-m-d H:i:s'),
                'limit' => $deleteLimit,
            ));
            $logStartMicrotime = microtime(1);
            $logStatus = 'good';
            $logID = self::getDatabase()->lastInsertId();
            
            self::getDatabase()->update("dnpl_types", array(
                'tech_paused' => 1,
            ), "id={$id}");
            
            if(self::getTypeData($id, 'type') == self::TYPE_SSN_EMAIL_PARTS || self::getTypeData($id, 'type') == self::TYPE_MD5_SSN || self::getTypeData($id, 'type') == self::TYPE_MD5_SSNDOPRESENT){
                try {
                    $res = self::getDatabase()->query("delete from dnpl_data_{$id} where `actual` < ? limit {$deleteLimit}", date('Y-m-d'));
                    $rowsCount = $res->rowCount();
                    
                    // это можно делать тольок тогда когда не приходят пинги или логировать пинги куда то отдельно. а потом присоединять
                    // self::getDatabase()->query("OPTIMIZE TABLE dnpl_data_{$id}");  
                }
                catch(Exception $e){
                    $logStatus = 'bad';
                    echo "Code: " . $e->getCode() . " \r\n";
                    echo "Message: " . $e->getMessage() . " \r\n";
                    echo "Trace: " . $e->getTraceAsString() . " \r\n";
                }
            }
            
            self::getDatabase()->update("dnpl_types", array(
                'tech_paused' => 0,
            ), "id={$id}");
            
            self::getDatabase()->update("dnpl_clear_log", array(
                'status'    => $logStatus,
                'end'       => date('Y-m-d H:i:s'),
                'rows'      => $rowsCount,
                'runtime'   => microtime(1) - $logStartMicrotime,
            ), "id={$logID}");
        }
    }
    
    /**
    * Обновить лист SSN-EMAIL - ов
    * 
    * @param mixed $id
    * @param mixed $list - array of array(ssn,email)
    */
    static public function updateFullSSNEMAILList($id, $list, $clearCurrentList = false){
        $clearCurrentList = (bool)$clearCurrentList;
        
        $result = array(
            'success' => 1,
            'reason'  => '',
            'runtime' => microtime(1),
        );
        
        $actual = date('Y-m-d H:i:s', time() + (86400*3650)); // на всякий случай прибавим еще 1 день
        
        if(self::getTypeData($id, 'id')){
            if(!self::getTypeData($id, 'tech_paused')){
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 1,
                ), "id={$id}");
                
                $data = array();
                foreach($list as $el){
                    if(count($el)==2 || count($el)==3){
                        $cur_actual = $actual;
                        if (isset($el[2]) && (int)$el[2]>0){
                            $days = (int)$el[2];
                            $cur_actual = date('Y-m-d H:i:s', time() + (86400*$days));    
                        }
                        $data[] = array(
                            'ssn_part'              => self::getSSNPart($el[0]),
                            'email_part_crc32'      => self::getEmailPartCRC32($el[1]),   
                            'email_part_checksum'   => self::getEmailPartChecksum($el[1]),
                            'actual'                => $cur_actual,
                        );
                    }
                } 
                if($clearCurrentList){
                    self::getDatabase()->query("DROP TABLE IF EXISTS dnpl_data_{$id}");
                    self::getDatabase()->query("CREATE TABLE `dnpl_data_{$id}` (                            
                                   `id` int(1) unsigned NOT NULL auto_increment,         
                                   `ssn_part` smallint(1) unsigned default NULL,         
                                   `email_part_crc32` int(1) unsigned default NULL,      
                                   `email_part_checksum` int(1) unsigned default NULL,   
                                   `actual` date default NULL,                           
                                   PRIMARY KEY  (`id`),                                  
                                   KEY `main` (`ssn_part`,`email_part_crc32`),           
                                   KEY `actual` (`actual`)                               
                                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                }
                
                if(count($data)){  
                    self::getDatabase()->insertMulty("dnpl_data_{$id}", array('ssn_part','email_part_crc32','email_part_checksum','actual'), $data, 500);
                } 
                
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 0,
                    'last_update' => date("Y-m-d H:i:s")
                ), "id={$id}");   
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'Can not update now, since channel is in a technical pause, try again in 5 minutes, and then contact the administrator'; 
            }
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Channel Not Found'; 
        }
        
        $result['runtime'] = microtime(1) - $result['runtime'];
        
        return $result;
    }
    
    /**
    * Обновить лист SSN-ов
    * 
    * @param mixed $id
    * @param mixed $data
    */
    static public function updateFullSSNList($id, $data, $clearCurrentList = false){
        $clearCurrentList = (bool)$clearCurrentList;
        
        $result = array(
            'success' => 1,
            'reason'  => '',
            'runtime' => microtime(1),
        );
        
        if(self::getTypeData($id, 'id')){
            if(!self::getTypeData($id, 'tech_paused')){
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 1,
                ), "id={$id}");
                
                $all = explode("\n", $data);
                $data = array();
                foreach($all as $el){
                    $el = (int)$el;
                    if($el > 0 && $el < 1000000000){
                        $data[] = array(
                            'ssn' => (int)$el
                        );
                    }
                } 
                
                if($clearCurrentList){
                    self::getDatabase()->query("DROP TABLE IF EXISTS dnpl_data_{$id}");
                    self::getDatabase()->query("CREATE TABLE `dnpl_data_{$id}` (                           
                        `id` int(1) unsigned NOT NULL auto_increment,        
                        `ssn` int(1) unsigned default NULL,                               
                        PRIMARY KEY  (`id`),                                 
                        KEY `main` (`ssn`)                                     
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                }
                
                if(count($data)){  
                    self::getDatabase()->insertMulty("dnpl_data_{$id}", array('ssn'), $data, 500);
                } 
                
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 0,
                    'last_update' => date("Y-m-d H:i:s")
                ), "id={$id}");   
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'Can not update now, since channel is in a technical pause, try again in 5 minutes, and then contact the administrator'; 
            }
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Channel Not Found'; 
        }
        
        $result['runtime'] = microtime(1) - $result['runtime'];
        
        return $result;
    }
    
    static public function updateFullSSNListFromArray($id, $dataarray, $clearCurrentList = false){
        $clearCurrentList = (bool)$clearCurrentList;
        
        $result = array(
            'success' => 1,
            'reason'  => '',
            'runtime' => microtime(1),
        );
        
        if(self::getTypeData($id, 'id')){
            if(!self::getTypeData($id, 'tech_paused')){
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 1,
                ), "id={$id}");
                
                $data = array();
                foreach($dataarray as $el){
                    $el = (int)$el;
                    if($el > 0 && $el < 1000000000){
                        $data[] = array(
                            'ssn' => sha1((int)$el)
                        );
                    }
                } 
                
                if($clearCurrentList){
                    self::getDatabase()->query("DROP TABLE IF EXISTS dnpl_data_{$id}");
                    self::getDatabase()->query("CREATE TABLE `dnpl_data_{$id}` (                           
                        `id` int(1) unsigned NOT NULL auto_increment,        
                        `ssn` varchar(32) DEFAULT NULL,
                        PRIMARY KEY  (`id`),                                 
                        KEY `main` (`ssn`)                                     
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                }
                
                if(count($data)){  
                    self::getDatabase()->insertMulty("dnpl_data_{$id}", array('ssn'), $data, 500);
                } 
                
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 0,
                    'last_update' => date("Y-m-d H:i:s")
                ), "id={$id}");   
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'Can not update now, since channel is in a technical pause, try again in 5 minutes, and then contact the administrator'; 
            }
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Channel Not Found'; 
        }
        
        $result['runtime'] = microtime(1) - $result['runtime'];
        
        return $result;
    }
    
    static public function updateSHA1SSNList($id, $data, $clearCurrentList = false){
        $clearCurrentList = (bool)$clearCurrentList;
        
        $result = array(
            'success' => 1,
            'reason'  => '',
            'runtime' => microtime(1),
        );
        
        if(self::getTypeData($id, 'id')){
            if(!self::getTypeData($id, 'tech_paused')){
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 1,
                ), "id={$id}");
                
                if($clearCurrentList){
                    self::getDatabase()->query("DROP TABLE IF EXISTS dnpl_data_{$id}");
                    self::getDatabase()->query("CREATE TABLE `dnpl_data_{$id}` (                           
                        `id` int(1) unsigned NOT NULL auto_increment,        
                        `ssn` varchar(40) default NULL,
                        `storeid` varchar(12) default NULL,                               
                        PRIMARY KEY  (`id`),                                 
                        KEY `main` (`ssn`),
                        KEY `storeid` (`storeid`)                                     
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                }
                
                if(count($data)){  
                    self::getDatabase()->insertMulty("dnpl_data_{$id}", array('ssn','storeid'), $data, 500);
                } 
                
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 0,
                    'last_update' => date("Y-m-d H:i:s")
                ), "id={$id}");   
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'Can not update now, since channel is in a technical pause, try again in 5 minutes, and then contact the administrator'; 
            }
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Channel Not Found'; 
        }
        
        $result['runtime'] = microtime(1) - $result['runtime'];
        
        return $result;
    }
    
    /**
    * Обновить лист md5(ssn)
    * 
    * @param mixed $id
    * @param mixed $data
    */
    static public function updateFullmd5SSNList($id, $data, $clearCurrentList = false){
        $result = array(
            'success' => 1,
            'reason'  => '',
            'runtime' => microtime(1),
        );
        $actual = date('Y-m-d', time() + (86400*3650)); // на всякий случай прибавим еще 1 день
        if(self::getTypeData($id, 'id')){
            if(!self::getTypeData($id, 'tech_paused')){
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 1,
                ), "id={$id}");
                
                $all = explode("\n", $data);
                $data = array();
                foreach($all as $el){ 
                    $el = trim($el); 
                    if(strlen($el)==32){
                        $data[] = array(
                            'ssn' => $el,
                            'actual' => $actual,
                        );
                    }
                } 
                if($clearCurrentList){
                    self::getDatabase()->query("DROP TABLE IF EXISTS dnpl_data_{$id}");
                    self::getDatabase()->query("CREATE TABLE `dnpl_data_{$id}` (                            
                                       `id` int(1) unsigned NOT NULL auto_increment,         
                                       `ssn` varchar(32) default NULL,            
                                       `actual` date default NULL,                           
                                       PRIMARY KEY  (`id`),                                  
                                       KEY `main` (`ssn`),           
                                       KEY `actual` (`actual`)                               
                                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                }
                
                if(count($data)){  
                    self::getDatabase()->insertMulty("dnpl_data_{$id}", array('ssn', 'actual'), $data, 500);
                } 
                
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 0,
                    'last_update' => date("Y-m-d H:i:s")
                ), "id={$id}");   
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'Can not update now, since channel is in a technical pause, try again in 5 minutes, and then contact the administrator'; 
            }
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Channel Not Found'; 
        }
        
        $result['runtime'] = microtime(1) - $result['runtime'];
        
        return $result;
    }
    
    /**
    * Обновить лист Emails
    * 
    * @param mixed $id
    * @param mixed $data
    */
    static public function updateFullEmailsList($id, $data){
        $result = array(
            'success' => 1,
            'reason'  => '',
            'runtime' => microtime(1),
        );
        
        if(self::getTypeData($id, 'id')){
            if(!self::getTypeData($id, 'tech_paused')){
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 1,
                ), "id={$id}");
                
                $all = explode("\n", $data);
                $data = array();
                foreach($all as $el){ 
                    $el = trim($el); 
                    if(strlen($el) && strpos($el, "@")){
                        $data[] = array(
                            'email_crc32' => self::getEmailCRC32($el),
                            'email_checksum' => self::getEmailChecksum($el),
                        );
                    }
                } 
                
                self::getDatabase()->query("DROP TABLE IF EXISTS dnpl_data_{$id}");
                self::getDatabase()->query("CREATE TABLE `dnpl_data_{$id}` (                           
                    `id` int(1) unsigned NOT NULL auto_increment,               
                    `email_crc32` int(1) unsigned default NULL,     
                    `email_checksum` int(1) unsigned default NULL,                         
                    PRIMARY KEY  (`id`),                                 
                    KEY `main` (`email_crc32`)                                     
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                
                if(count($data)){  
                    self::getDatabase()->insertMulty("dnpl_data_{$id}", array('email_crc32', 'email_checksum'), $data, 500);
                } 
                
                self::getDatabase()->update("dnpl_types", array(
                    'tech_paused' => 0,
                    'last_update' => date("Y-m-d H:i:s")
                ), "id={$id}");   
            }
            else {
                $result['success'] = 0;
                $result['reason']  = 'Can not update now, since channel is in a technical pause, try again in 5 minutes, and then contact the administrator'; 
            }
        }
        else {
            $result['success'] = 0;
            $result['reason']  = 'Channel Not Found'; 
        }
        
        $result['runtime'] = microtime(1) - $result['runtime'];
        
        return $result;
    }
    
    /**
    * Поиск записи в базе данных
    * 
    * @param mixed $id
    * @param mixed $email
    * @param mixed $ssn
    */
    static public function isNotPost($id, $ssn = null, $email = null, $lead_id = null,$channel_id = null){
        // Если база не найдена или она в технической паузе, пропускаем проверку
        $return = false;
        if(self::getTypeData($id, 'id') && !self::getTypeData($id, 'tech_paused') && self::getTypeData($id, 'active')){
            if(self::getTypeData($id, 'type') == self::TYPE_SSN_EMAIL_PARTS){
                if(strlen($ssn) && strlen($email)){
                    try{
                        $return = (bool)self::getDatabase()->fetchOne(
                            "select id from dnpl_data_{$id} where ssn_part=? and email_part_crc32=? and email_part_checksum=? limit 1",
                            array(
                                self::getSSNPart($ssn),
                                self::getEmailPartCRC32($email),
                                self::getEmailPartChecksum($email),
                            )
                        );
                    }
                    catch(Exception $e){}
                }
            }
            else if(self::getTypeData($id, 'type') == self::TYPE_FULL_SSN){
                if(strlen($ssn)){
                    try{
                        $return = (bool)self::getDatabase()->fetchOne("select id from dnpl_data_{$id} where ssn=? limit 1", sha1((int)$ssn));
                    }
                    catch(Exception $e){}
                }
            }                                        
            else if(self::getTypeData($id, 'type') == self::TYPE_FULL_EMAIL){
                if(strlen($email)){
                    try{
                        $return = (bool)self::getDatabase()->fetchOne(
                            "select id from dnpl_data_{$id} where email_crc32=? and email_checksum=? limit 1",
                            array(
                                self::getEmailCRC32($email),
                                self::getEmailChecksum($email),                          
                            )
                        );
                    }
                    catch(Exception $e){}
                }
            }
            else if(self::getTypeData($id, 'type') == self::TYPE_MD5_SSN){
                if(strlen($ssn)){
                    try{
                        $return = (bool)self::getDatabase()->fetchOne("select id from dnpl_data_{$id} where ssn=? limit 1", md5($ssn));
                    }
                    catch(Exception $e){}
                }
            }else if(self::getTypeData($id, 'type') == self::TYPE_MD5_SSNDOPRESENT){
                if(strlen($ssn)){
                    try{
                        $isset_dopresent = (bool)self::getDatabase()->fetchOne("select id from dnpl_data_{$id} where ssn=? limit 1", md5($ssn));
                        if ($isset_dopresent){
                            $return = false;     
                        }else{
                            $return = true;    
                        }
                    }
                    catch(Exception $e){}
                }
            } 
                
        }    
        
        if ($return){
            self::addStatItem($lead_id,$channel_id,$id);    
        }
        return $return;
    }
    
    static public function isNotPostSHA1($id, $ssn = null, $storeid = null, $lead_id = null,$channel_id = null){
        // Если база не найдена или она в технической паузе, пропускаем проверку
        $return = false;
        if(self::getTypeData($id, 'id') && !self::getTypeData($id, 'tech_paused') && self::getTypeData($id, 'active')){
            if (self::getTypeData($id, 'type') == self::TYPE_SHA1_SSNDOPRESENT){
                if(strlen($ssn)){
                    try{
                        $isset_dopresent = (bool)self::getDatabase()->fetchOne("select id from dnpl_data_{$id} where ssn=? and storeid=? limit 1", array(sha1($ssn),$storeid));
                        if ($isset_dopresent){
                            $return = false;     
                        }else{
                            $return = true;    
                        }
                    }
                    catch(Exception $e){}
                }
            } 
                
        }    
        
        if ($return){
            self::addStatItem($lead_id,$channel_id,$id);    
        }
        return $return;
    }
    
    static public function addStatItem($lead_id,$channel_id,$dnp_list){
        /*try {                            
            self::getDatabase()->insert("dnpl_stat", array(
                'lead_id'         => $lead_id,
                'channel_id'      => $channel_id,   
                'dnp_list'        => $dnp_list,
                'date'            => date('Y-m-d H:i:s')
            ));   
        }
        catch(Exception $e){}*/    
    }
    
    static public function getListCount($id){
        $res = 0;
        try{
            $res = self::getDatabase()->fetchOne("select count(*) from dnpl_data_{$id}");
        }
        catch(Exception $e){}
        return (int)$res;
    }
    
    static public function getDNPListsForSelectbox($product){
        return self::getDatabase()->fetchPairs("select id, title from dnpl_types where product=? and `active`=1", $product);
    }
    
    static public function getDNPBuyers(){
        return self::getDatabase()->fetchAll("SELECT buyers_channels.buyer_id,users_company.`systemName` FROM buyers_channels,users_company WHERE buyers_channels.id IN (SELECT channel_id FROM buyers_filters_conditions WHERE type_name='DoNotPresentList' AND works=1 GROUP BY channel_id) AND buyers_channels.`buyer_id`=users_company.`id` GROUP BY buyers_channels.buyer_id");
    }
}
