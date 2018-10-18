<?php

class T3BuyerChannel_Reasons {
    static protected $insertModeRealTime = true;
    static protected $commitArray = array();
    static protected $reasonsCache = array();
    
    /**
    * Получение одного ID, не оптимально если ID надо получить сразу для нескольких новых, требующих подгрузку
    * 
    * @param mixed $reason
    */
    static public function getReasonId($reason){     
        $reason = substr($reason, 0, 128);
    
        $md5 = md5($reason); 
        if(isset(self::$reasonsCache[$md5])){
            $id = self::$reasonsCache[$md5];     
        }
        else {
            $id = T3Db::api()->fetchOne("select id from buyer_channels_reasons_base where reason=?", $reason);
            if($id === false){
                try{
                    T3Db::api()->insert("buyer_channels_reasons_base", array(
                        'reason'    =>  $reason,
                        'md5'       =>  md5($reason),
                    ));
                    
                    $id = T3Db::api()->lastInsertId();
                }
                catch(Exception $e){
                    $id = (int)T3Db::api()->fetchOne("select id from buyer_channels_reasons_base where reason=?", $reason);   
                }
            }
            self::$reasonsCache[$md5] = $id;
        }
        return $id;    
    }
    
    static public function getReasonName($id){
	    $name = T3Db::api()->fetchOne("select reason from buyer_channels_reasons_base where id=?", $id);
	    return $name;
    }
    
    static public function getReasonViewed($id){
        $name = T3Db::api()->fetchOne("select viewed from buyer_channels_reasons_log where reason_id=?", $id);
	    return $name;
    }
    
    /**
    * Добавить данные
    * Они или сразу записываются в базу или добавляются в очередь, которую в дальнейшем можно будет сохранить функцие commit.
    * Это зависит от режима сохранения $insertModeRealTime
    * 
    * @param int $posting
    */
    static public function add($reason, $leadID, $postingID){
        $reason = substr($reason, 0, 128);
        if(self::$insertModeRealTime){
            // Сохраненеи в реальном времени
            T3Db::api()->insert("buyer_channels_reasons_log", array(
                'lead_id'           => $leadID,
                'posting_id'        => $postingID,
                'posting_product'   => T3Products::getID(T3BuyerChannels::getChannel($postingID)->product),
                'reason_id'         => self::getReasonId($reason), 
                'recived_datetime'  => date("Y-m-d H:i:s"),
            ));
        }
        else{
            // Сохранение в очередь
            self::$commitArray[] = array(
                'lead_id'           => $leadID,
                'posting_id'        => $postingID,
                'posting_product'   => T3Products::getID(T3BuyerChannels::getChannel($postingID)->product),
                'reason'            => $reason,
                'recived_datetime'  => date("Y-m-d H:i:s"),
            );
        }                            
    }     
    
    /**
    * Сохранить накопленные данные в базу
    */
    static public function commit($end = true){
        // Запись всей очереди в базу   
        if(count(self::$commitArray)){
            foreach(self::$commitArray as $k => $v){
                self::$commitArray[$k]['reason'] = self::getReasonId($v['reason']);
                //unset(self::$commitArray[$k]['reason']);       
            }
            T3Db::api()->insertMulty("buyer_channels_reasons_log", array('lead_id', 'posting_id', 'posting_product', 'reason_id', 'recived_datetime'), self::$commitArray);
            self::$commitArray = array();
        }
        
        if($end) self::setInsertModeRealTime(true);
    }
    
    static public function setInsertModeRealTime($flag){
        self::$insertModeRealTime = (bool)$flag;    
    }
    
    /**    
    * @param mixed $reasonID
    * @param mixed $postingID
    * @param mixed $type
    * @param mixed $forUI    true - для интерфейсе, false для письма
    * @return AZend_Table
    */
    static public function createDetailsTable($reasonID, $postingID, $type, $forUI = true){
        $leadObj = new T3Lead("payday");
        
        // типы данных
        $types = array(
            'ABA'           => array('bank_name', 'bank_aba'),
            'State'         => array('state'),
            'Income'        => array('monthly_income'),
            'Employment'    => array('employer'),
            'Bank'          => array('bank_name'),
	    'Clarity'       => array('state','bank_aba'),
        );
        
        $reasons = T3Db::api()->fetchAll("select 
            buyer_channels_reasons_log.id, 
            buyer_channels_reasons_log.lead_id, 
            buyer_channels_reasons_log.recived_datetime,
            leads_data.data_email,
            leads_data.datetime,
            leads_data.affid
        from  
            buyer_channels_reasons_log   
            join buyer_channels_reasons_base on buyer_channels_reasons_log.reason_id=buyer_channels_reasons_base.id 
            join leads_data on (leads_data.id = buyer_channels_reasons_log.lead_id)
        where 
            buyer_channels_reasons_log.posting_id='" . (int)$postingID . "' and 
            buyer_channels_reasons_log.reason_id='" . (int)$reasonID . "'   
        order by id desc 
        limit 200");  

        // преобразование массива
        if(count($reasons)){  
            // добавление переменных для определенных типов странц
            if(isset($types[$type])){
                foreach($reasons as $k => $v){
                    foreach($types[$type] as $name){
                        $lead = new T3Lead();
                        $lead->fromDatabase($v['lead_id']);
                        
                        $reasons[$k]['_' . $name] = ifset($lead->getBodyFromDatabase()->$name);     
                    }      
                }   
            }
        }

        // создание таблицы
        $table = AZend_Table::createTable($reasons);
        
        if($forUI) $table->addField_DotsSelectTr();
        $table->addField_LeadID('lead_id', 'Lead ID');
        $table->addField_Publisher('affid', "Publisher");
        $table->addField('data_email', 'Email');
        $table->addField_Date('datetime', 'Create Lead');

        // дополнительные поля
        if(isset($types[$type])){
            foreach($types[$type] as $name){
                $el = $leadObj->getBody()->zendForm->getElement($name);
                
                if(is_object($el)) $label = $el->getLabel();
                else               $label = $name;
                
                $table->addField('_' . $name, $label);   
            }  
        }
        
        return $table;
    }
}