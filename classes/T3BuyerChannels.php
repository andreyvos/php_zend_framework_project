<?php

class T3BuyerChannels{

    protected static $_instance = null;
    protected $channelStatuses = array('active', 'just_created', 'paused');  
    public $database;
    
    
    public $duplicateCache = array();
    
    static protected $channels = array();
    /**
    * Загрузить объект баера (компании)
    * 
    * @param int $id
    * @return T3BuyerChannel
    */
   static public function getChannel($id){
        if(!isset(self::$channels[$id])){
            self::$channels[$id] = new T3BuyerChannel();
            self::$channels[$id]->fromDatabase($id);
        } 
        
        return self::$channels[$id];   
    }
    
    
    /*****************************************************************
    * 
    * Пул загрузки данных о канале
    * при продаже лида по пингтри, каналы загружаются один за другим
    * что бы каждый раз не грузить его из базы данных, делается пул, который загружает данные порциями по X
    * 
    * А также пул загрузки данных фильтров
    * 
    * 
    *****/
    
    /**
    * Список потенциальных каналов на загрузку, выстанные по порядку загрузки
    * 
    * @var array
    */
    static protected $poolPotencialIDS = array();
    
    /**
    * Количесво каналов, которые будут загружаться за 1 раз
    * 
    * @var int
    */
    static protected $poolPacketChannelsCount = 200;
    
    /**
    * Количесво настроек фильтров, которые будут загружаться за 1 раз
    * 
    * @var int
    */
    static protected $poolPacketFiltersCount = 10;
    
    /**
    * Пул данных о каналах
    * 
    * @var array
    */
    static protected $channelsDataPool = array();
    
    /**
    * Пул данных о фильтрах
    * 
    * @var array
    */
   static protected $filtersDataPool = array();

   static public function getAllPotencialIDS(){
       return self::$poolPotencialIDS;
   }

    /**
    * Задать последовательность загрузки каналов, котрорая будет использоваться при продаже
    * 
    * @param mixed $postingsIds
    */
   static public function setPotencialPoolIDS($postingsIds){
       if(is_array($postingsIds) && count($postingsIds)){
           foreach($postingsIds as $id){
               if(is_numeric($id) && !in_array($id, self::$poolPotencialIDS)) self::$poolPotencialIDS[] = $id;
           }
       }
   }
    
    /**
    * Получить данные канала.
    * Если задана последовательность, то запросы будут группироваться
    * 
    * @param int $channelID id канала, по которому надо получить данные
    */
    static public function getPoolChannelsData($channelID){
        $channelID = (int)$channelID;
        
        if(!isset(self::$channelsDataPool[$channelID])){
            if(in_array($channelID, self::$poolPotencialIDS)){
                // канал находиться в пуле
                $start = array_search($channelID, self::$poolPotencialIDS);
                $finish = min($start + self::$poolPacketChannelsCount, count(self::$poolPotencialIDS));
                 
                $pool = array();
                for($i = $start; $i < $finish; $i++){
                    $cid = self::$poolPotencialIDS[$i];    
                    if($cid && !isset(self::$channelsDataPool[$cid])){
                        $pool[] = $cid; 
                        self::$channelsDataPool[$channelID] = false;       
                    }           
                } 
                
                $all = T3Db::api()->fetchAll("select * from buyers_channels where id in (" . implode(",", $pool) . ")");
                if(count($all)){
                    foreach($all as $el){
                        self::$channelsDataPool[$el['id']] = $el;    
                    }
                }
            }
            else {
                // канала нет в пуле
                self::$channelsDataPool[$channelID] = T3Db::api()->fetchRow("select * from buyers_channels where id=? limit 1", $channelID);
            }
        }   
        
        return self::$channelsDataPool[$channelID]; 
    }
    
    /**
    * Получить данные фильтров канала
    * Если задана последовательность, то запросы будут группироваться
    * 
    * @param int $channelID id канала, по которому надо получить данные
    */
    static public function getPoolFiltersData($channelID){
        $channelID = (int)$channelID;
        
        if(!isset(self::$filtersDataPool[$channelID])){
            if(in_array($channelID, self::$poolPotencialIDS)){
                // фильтр находиться в пуле
                $start = array_search($channelID, self::$poolPotencialIDS);
                $finish = min($start + self::$poolPacketFiltersCount, count(self::$poolPotencialIDS));
                 
                $pool = array();
                for($i = $start; $i < $finish; $i++){
                    $cid = self::$poolPotencialIDS[$i];    
                    if($cid && !isset(self::$filtersDataPool[$cid])){
                        $pool[] = $cid; 
                        self::$filtersDataPool[$channelID] = array();      
                    }           
                } 
                
                //varExport($pool);
                
                $all = T3Db::api()->fetchAll("SELECT * FROM buyers_filters_conditions WHERE channel_id in (" . implode(",", $pool) . ") and works=1");
                
                if(count($all)){
                    foreach($all as $el){
                        self::$filtersDataPool[$el['channel_id']][] = $el;    
                    }
                }
            }
            else {
                // фильтров нет в пуле
                self::$filtersDataPool[$channelID] = T3Db::api()->fetchAll("SELECT * FROM buyers_filters_conditions WHERE channel_id = ? and works=1", $channelID); 
            }
        }   
        
        return self::$filtersDataPool[$channelID]; 
    }
    
    
    /*******************************/ 

    protected function  initialize() {
        $this->database = T3Db::api();
    }

    /**
    * @return T3BuyerChannels
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }

    // /account/posting/list/
    // Возврящает Список каналов с сортировками - Именно Buyers

    /*
    $order = array(
      'field1',
      'field2',
      array('field3', 'asc'),
      array('field4', 'desc')
    );
    */

    public function getChannelStatuses() {
        return $this->channelStatuses;
    }
    
    static protected $disabled_saving_email = array(
        //'payday'
    );
    
    static protected $disabled_saving_ssn = array(
        'ukpayday',
        'payday_optin',
        'uk_payday_optin',
    );
    
    static public function addDuplicateItem(T3Lead $lead, T3BuyerChannel $posting){
        if($lead->id != 999999){
            
            if(!in_array($lead->product, self::$disabled_saving_ssn)){
                if($lead->data_ssn){
                    T3Db::api()->insert("buyer_channels_dup_ssn_sold", array(
                        'ssn'       => (int)($lead->data_ssn),
                        'posting'   => (int)$posting->id,
                        'date'      => new Zend_Db_Expr("NOW()"),
                    ));
                } 
            }
            
            if(!in_array($lead->product, self::$disabled_saving_email)){  
                if(strlen($lead->data_email)){
                    T3Db::api()->insert("buyer_channels_dup_email_sold", array(
                        'email'     => substr($lead->data_email, 0, 32),
                        'posting'   => (int)$posting->id,   
                        'date'      => new Zend_Db_Expr("NOW()"), 
                    ));
                }  
            }
            
        }    
    }
    
    static public function addDuplicatePostItem(T3Lead $lead, T3BuyerChannel $posting){
        if($lead->id != 999999){ 
            if(!in_array($lead->product, self::$disabled_saving_ssn)){ 
                if($lead->data_ssn){
                    T3Db::api()->insert("buyer_channels_dup_ssn_post", array(
                        'ssn'       => (int)$lead->data_ssn,
                        'posting'   => (int)$posting->id,
                        'date'      => new Zend_Db_Expr("NOW()"),
                    ));
                }  
            }
            
            if(!in_array($lead->product, self::$disabled_saving_email)){ 
                if(strlen($lead->data_email)){
                    T3Db::api()->insert("buyer_channels_dup_email_post", array(
                        'email'     => substr($lead->data_email, 0, 32),
                        'posting'   => (int)$posting->id,   
                        'date'      => new Zend_Db_Expr("NOW()"), 
                    ));
                } 
            }
            
        }   
    }
    
    static public function addDuplicateGlobalItem(T3Lead $lead){
        if($lead->id != 999999){    
            
            if(!in_array($lead->product, self::$disabled_saving_ssn)){ 
                if($lead->data_ssn){
                    T3Db::api()->insert("buyer_channels_dup_ssn_global", array(
                        'ssn'       => (int)($lead->data_ssn),
                        'product'   => (int)T3Products::getID($lead->product),
                        'lead'      => (int)$lead->id,
                        'date'      => new Zend_Db_Expr("NOW()"),
                    ));
                }
            }
            
            if(!in_array($lead->product, self::$disabled_saving_email)){ 
                if(strlen($lead->data_email)){
                    T3Db::api()->insert("buyer_channels_dup_email_global", array(
                        'email'     => substr($lead->data_email, 0, 32),
                        'product'   => (int)T3Products::getID($lead->product),
                        'lead'      => (int)$lead->id,
                        'date'      => new Zend_Db_Expr("NOW()"), 
                    )); 
                }
            }
            
            if ($lead->product == 'call'){
                if(strlen($lead->data_phone)){
                    T3Db::api()->insert("buyer_channels_dup_phone_global", array(
                        'phone'     => $lead->data_phone,
                        'product'   => (int)T3Products::getID($lead->product),
                        'lead'      => (int)$lead->id,
                        'date'      => new Zend_Db_Expr("NOW()"), 
                    )); 
                }    
            }         
        }    
    }
    
    static public function checkDuplicate(T3Lead $lead, $posting){
        if(is_int($posting)){
            $postingID = $posting;
            $posting = new T3BuyerChannel();
            $posting->fromDatabase($postingID);    
        }
        
        if(is_object($posting) && is_a($posting, "T3BuyerChannel") && $posting->id){
            /** @var T3BuyerChannel */
            $posting;                       
        }
        else {
            // Переданны ошибочные данные, невозможно проверить на дупликат
            return false;    
        }
        
        if(!isset(self::getInstance()->duplicateCache[$lead->id][$posting->id])){
            $duplicate_values = array();
            
            $namesRelations = array(
                'email'     =>  'data_email',
                //'ssn'       =>  'data_ssn',
                //'phone'     =>  'data_home_phone',
            );
            
            $classNames = $lead->getBody()->getMainValues();
            foreach($namesRelations as $key => $dbName){
                $valueName = $classNames[$key];
                
                if($valueName && $lead->getBody()->$valueName){
                    $duplicate_values[] = "`{$dbName}` = " . T3System::getConnect()->quote($lead->getBody()->$valueName);    
                }    
            }
            
            self::getInstance()->duplicateCache[$lead->id][$posting->id] = false;
            
            // Если канал входит в One Company группу, то проверка будет по всем каналам из группы.
            $postings = array();
            $PostingConfigOneCompany = T3Db::api()->fetchCol("SELECT groupName FROM buyers_channels_onecompany WHERE idposting=?", $posting->id);
            if(count($PostingConfigOneCompany)){
                $postings = T3Db::api()->fetchCol("select DISTINCT idposting from buyers_channels_onecompany where groupName in ('" . implode("','", $PostingConfigOneCompany) . "')");
                if(!is_array($postings)) $postings = array();
                if(!count($postings) || !in_array($posting->id, $postings)) $postings[] = $posting->id; 
            }
            if(!is_array($postings) || count($postings) == 0){
                $postings = array($posting->id);    
            }   
            
            //varExport($duplicate_values);
            //varExport($postings);
            
            
            $methods = array('email');       
            
            if($posting->product == 'payday'){    
                $methods = array('ssn'); 
            }
            
            
            if($posting->duplicateMethod == 2){
                // Only Email
                $methods = array('email');
            }
            else if($posting->duplicateMethod == 3){
                // Only SSN
                $methods = array('ssn');   
            }
            else if($posting->duplicateMethod == 4){
                // SSN + Email
                $methods = array('ssn', 'email');     
            }
            
            
            
            foreach($methods as $method){ 
                $table_type     = $method;
                $table_value    = $method;
                $lead_value     = $method;  
              
                // Если нет аргументов для поиска дублированного лида, то не проврдить этот поиск
                if(count($duplicate_values) && isset($lead->getBody()->$lead_value)){
                    // дупликаты по солдам
                    // Проходит поиск, только если количество дней поиска больше чем для поиска по постам.
                    if(self::getInstance()->duplicateCache[$lead->id][$posting->id] === false){ 
                        if($posting->duplicateDays > $posting->duplicatePostDays){

                            $val_for_check = substr($lead->getBody()->$lead_value, 0, 32);
                            if ($table_type == 'ssn'){
                                $val_for_check = T3SSN::getID($val_for_check);
                            }

                            self::getInstance()->duplicateCache[$lead->id][$posting->id] = (bool)T3Db::api()->fetchOne(
                                "select id from buyer_channels_dup_{$table_type}_sold where `{$table_value}`=? and ".
                                "`posting` in ('" . implode("','", $postings) . "') and `date`>=? limit 1", array(
                                    $val_for_check,
                                    date("Y-m-d", mktime(0,0,0, date("m"), date("d") - $posting->duplicateDays, date("Y")))
                                )   
                            );
                        }
                    }  
                    
                    // Если еще не найден дупликат для лида
                    if(self::getInstance()->duplicateCache[$lead->id][$posting->id] === false){
                        // дупликаты по просмотрам   
                        if($posting->duplicatePostDays >=0){

                            $val_for_check = substr($lead->getBody()->$lead_value, 0, 32);
                            if ($table_type == 'ssn'){
                                $val_for_check = T3SSN::getID($val_for_check);
                            }

                            self::getInstance()->duplicateCache[$lead->id][$posting->id] = (bool)T3Db::api()->fetchOne(
                                "select id from buyer_channels_dup_{$table_type}_post where `{$table_value}`=? and ".
                                "`posting` in ('" . implode("','", $postings) . "') and `date`>=? limit 1", array(
                                    $val_for_check,
                                    date("Y-m-d", mktime(0,0,0, date("m"), date("d") - $posting->duplicatePostDays, date("Y")))      
                                )
                            );  
                        }
                    }
                    
                    
                    // Глобальный дупликат
                    if(self::getInstance()->duplicateCache[$lead->id][$posting->id] === false){  
                        if($posting->isGlobalDuplicateNow()){

                            $val_for_check = substr($lead->getBody()->$lead_value, 0, 32);
                            if ($table_type == 'ssn'){
                                $val_for_check = T3SSN::getID($val_for_check);
                            }

                            self::getInstance()->duplicateCache[$lead->id][$posting->id] = (bool)T3System::getConnect()->fetchOne(
                                "select id from buyer_channels_dup_{$table_type}_global where `{$table_value}`=? and `product`=? and `lead`!=? and `date`>? limit 1", array(
                                    $val_for_check,
                                    T3Products::getID($posting->product),
                                    $lead->id,
                                    date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $posting->duplicateGlobalDays, date("Y")))
                                )
                            );     
                        }
                    }

                    // Глобальный дупликат,минимальное количество дупликатов
                    if(self::getInstance()->duplicateCache[$lead->id][$posting->id] === false){
                        if($posting->isMinGlobalDuplicateNow()){

                            $val_for_check = substr($lead->getBody()->$lead_value, 0, 32);
                            if ($table_type == 'ssn'){
                                $val_for_check = T3SSN::getID($val_for_check);
                            }

                            $total_nums = 0;
                            if ($posting->minDuplicateData>0){
                                try{
                                    $total_nums = T3System::getConnect()->fetchOne(
                                        "select count(id) from buyer_channels_dup_{$table_type}_global where `{$table_value}`=? and `product`=? and `lead`!=? and `date`>? limit ".(int)$posting->minDuplicateNums, array(
                                            $val_for_check,
                                            T3Products::getID($posting->product),
                                            $lead->id,
                                            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $posting->minDuplicateData, date("Y"))),

                                        )
                                    );
                                }catch(Exception $e){
                                    $total_nums = 0;
                                }
                            }else{
                                try{
                                    $total_nums = T3System::getConnect()->fetchOne(
                                        "select count(id) from buyer_channels_dup_{$table_type}_global where `{$table_value}`=? and `product`=? and `lead`!=? limit ".(int)$posting->minDuplicateNums, array(
                                            $val_for_check,
                                            T3Products::getID($posting->product),
                                            $lead->id,
                                        )
                                    );
                                }catch(Exception $e){
                                    $total_nums = 0;
                                }
                            }

                            if ($total_nums>=(int)$posting->minDuplicateNums){
                                self::getInstance()->duplicateCache[$lead->id][$posting->id] = false;
                            }else{
                                self::getInstance()->duplicateCache[$lead->id][$posting->id] = true;
                            }

                        }
                    }
                }
            }
        }
        
        return self::getInstance()->duplicateCache[$lead->id][$posting->id];    
    }

    public function getChannelsList_Array($where = array(), $order = array()) {
      return T3SimpleDbSelect::select('buyers_channels', $where, $order)->fetchAll();
    }
    
    static public function getChannelsTitles(){
        return T3Db::api()->fetchAll("SELECT buyers_channels.id, CONCAT(users_company_buyer.systemName, ' :: ', buyers_channels.title) as title
FROM buyers_channels INNER JOIN users_company_buyer ON (buyers_channels.buyer_id = users_company_buyer.id)
order by title");    
    }

    static public function getChannelTitle($id){
        return T3Db::api()->fetchRow("SELECT buyers_channels.id, CONCAT(users_company_buyer.systemName, ' :: ', buyers_channels.title) as title
FROM buyers_channels INNER JOIN users_company_buyer ON (buyers_channels.buyer_id = users_company_buyer.id) where buyers_channels.id='$id'
order by title");
    }

    public function getChannel_Array($id) {
        return DbSerializable::fromDatabaseStatic('buyers_channels', $id);
    }

    // /account/posting/main/?id=XXXX
    // Редактирование настоек постинга
    /*
    $data = array(
      'buyer_id' => ...
      'email' => ...
      'timezone' => ...
      'timeout' => ...
    );
    */
    public function editChannel_Array($id, $data) {
        $idField = TableDescription::get('buyers_channels')->idFieldName;
        if (isset($data[$idField]))
            unset($data[$idField]);
        $this->database->update('buyers_channels', $data, 'id = ' . $this->database->quote($id));
    }

    // /account/posting/main/?id=XXXX
    // Редактирование конфигов /classes/PostingFunctions/ExampleFunction.php
    // Это пример конфига, но вообще у конфиго будут названия связанные с id
    public function getChannelConfig($id) {
        if (!is_file(T3SYSTEM_BUYERS_CHANNELS_CONFIGS . DIRECTORY_SEPARATOR . "$id.php"))
            return "";
        return file_get_contents(T3SYSTEM_BUYERS_CHANNELS_CONFIGS . DIRECTORY_SEPARATOR . "$id.php");
    }

    public function setChannelConfig($id, $data) {
        file_put_contents(T3SYSTEM_BUYERS_CHANNELS_CONFIGS . DIRECTORY_SEPARATOR . "$id.php", $data);
    }

    // /account/posting/filters/?id=XXXX
    // Фильтры
    public function getChannelFilters_Array($id) {
        return T3BuyerFilters::getInstance()->getFilterConditions_Array($id);
    }

    public function setChannelFilters_Array($id, $data) {
        $filter = new T3BuyerFilter();
        $filter->channelId = $id;
        $filter->fromArray($data);
        $filter->saveToDatabase();
    }

    public function getCertainChannels($ids) {
      $data = $this->database->fetchAll("
        SELECT *
        FROM buyers_channels
        WHERE id IN (" . $this->database->quote($ids) . ")"
      );
      $result = array();
      foreach($data as $v) {
        $channel = new T3BuyerChannel();
        $channel->fromArray($v);
        $result[$v['id']] = $channel;
      }
      return $result;
    }
    // /account/posting/prices/?id=XXXX
    // 2.d. Настройка цен ???
    
    
    static public function getGlobalRejects_Array(){
        return self::getGroupsMainMethod_Array('buyers_channels_globalreject');  
    }
    
    static public function getOneCompany_Array(){
        return self::getGroupsMainMethod_Array('buyers_channels_onecompany');  
    }
    
    static protected function getGroupsMainMethod_Array($tableName){
        $obj = self::getInstance();
        
        $result = $obj->database->fetchAll("select ".
        "{$tableName}.groupname as groupName, ".
        "buyers_channels.title as channelTitle, ".
        "{$tableName}.idposting as idPosting, ".
        "users_company_buyer.systemName as buyerName ".
        "from {$tableName} ". 
        "left join buyers_channels on ({$tableName}.idposting = buyers_channels.id) ".
        "left join users_company_buyer on (buyers_channels.buyer_id = users_company_buyer.id) ".
        "order by {$tableName}.groupName, users_company_buyer.id"); 
        
        $data = array();
        
        foreach($result as $res){
            $data[$res['groupName']][] = $res;
        } 
        
        return $data;  
    } 
    
    static public function getChannelTypeTitle($name){
        switch($name) // переключающее выражение
        {
           case 'js_form':          return 'JavaScript Form';
           case 'post_channel':     return 'Server POST';
           default:                 return 'Unknown';
        }   
    }
    
    public function searchChannelsIds($search_id)
    {
      if($search_id != "") {  
          return $this->database->fetchAll("
            SELECT id, title
            FROM buyers_channels
            WHERE id LIKE '".$search_id."%'
            ORDER BY id ASC LIMIT 50 
          ");
      } else {
          return $this->database->fetchAll("
            SELECT id, title
            FROM buyers_channels
            ORDER BY id ASC LIMIT 50 
          ", array($search_id));
      }
    }
    
    public static function getOneGroupsByPostingId($posting_id)
    {
        $obj = self::getInstance();
        $posting_id = (int)$posting_id;
        
        return $obj->database->fetchAll(
            " select idposting, groupName from buyers_channels_onecompany  where idposting = ".$posting_id." "
        );
        
    }
    
    
}