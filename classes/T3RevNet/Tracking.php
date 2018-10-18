<?php

class T3RevNet_Tracking {
    const trackType_T3LeadsSold = 't3Sold';
    const trackType_OtherSold   = 'other';
    const trackType_All         = 'all';
    
    static protected $currentResult;
    
    static protected function setError($text){
        self::$currentResult['status'] = 'error'; 
        self::$currentResult['reason'] = $text;     
    }
    
    static protected function isType_T3LeadsSold(){
        return (bool)(ifset(self::$currentResult['type']) == self::trackType_T3LeadsSold);     
    }
    
    static protected function isType_OtherSold(){
        return (bool)(ifset(self::$currentResult['type']) == self::trackType_OtherSold);    
    }
    
    /**
    * провередение оплаты
    * 
    * @param mixed $leadId
    * @param enum $type
    */
    static public function track($leadId, $price, $type, $inLog, $pingIP){
        // проверка Id лида
        $validId = 0;
        $webmaster = 0;
        $account = ''; 
        $firstLeadId = '0';
        $afterSeconds = '0';  
        $LeadRevnet_wm = '0';
        
        if(T3RevNet_Leads::getLead($leadId)->id){
            $validId    = T3RevNet_Leads::getLead($leadId)->id;
            $webmaster  = T3RevNet_Leads::getLead($leadId)->webmaster;
            $account    = T3RevNet_Leads::getLead($leadId)->account;
            $firstLeadId = T3RevNet_Leads::getLead($leadId)->lead_id; 
            $afterSeconds = time() - strtotime(T3RevNet_Leads::getLead($leadId)->create_date);
            
            if($type == self::trackType_All){
                $lead = new T3Lead();
                $lead->fromDatabase(T3RevNet_Leads::getLead($leadId)->lead_id);
                $checksValues = array('data_email', 'data_phone', 'data_ssn');
                $checkNow = array();
                foreach($checksValues as $el){
                    if(strlen($lead->$el)){
                        $checkNow[] = "`{$el}`=" . T3Db::api()->quote($lead->$el);    
                    }    
                }
                
                if(count($checkNow)){
                    //varExport("select count(*) from leads_data where `datetime` > '" . date("Y-m-d H:i:s", time() - 300) . "' and affid='26918' and (" . implode(" or ", $checkNow) . ")" );
                    $LeadRevnet_wm = T3Db::api()->fetchOne(
                        "select wm from leads_data where `datetime` > '" . date("Y-m-d H:i:s", time() - 300) . "' and affid='26918' and (" . implode(" or ", $checkNow) . ") limit 1"
                    );
                    
                    $isRevNet = $LeadRevnet_wm+0;
                }
                else {
                    $isRevNet = false;    
                }
                
                if($isRevNet){
                    // определить приходил ли подобный лид в нашу сисему и если приходил то и цена вебмастера в нем больше 0
                    $type = self::trackType_T3LeadsSold;
                }
                else {       
                    // если не приходил, то
                    $type = self::trackType_OtherSold;
                }  
            }             
        }
        else {
            self::setError("Lead #{$leadId} Not Found");   
        } 
        
        self::$currentResult = array(
            'create_date'   =>  date("Y-m-d H:i:s"),
            'status'        =>  'success',   // Статус 'success', 'error'
            'reason'        =>  '',          // Описание ошибки, если она есть
            
            'type'          =>  '',          // Тип тракинга
            'leadId'        =>  $validId,    // ID лида вебмастра на который пришел тракинг
            'pingIP'        =>  $pingIP,     // IP c которого пришел пинг
            
            'webmaster'     =>  $webmaster,
            'account'       =>  $account,
            'firstLeadId'   =>  $firstLeadId,
            'afterSeconds'  =>  $afterSeconds,
            
            'data_email'    =>  '',
            
            'getPrice'      =>  '0',         // Сумму которую они прислали (Эту сумму должен ревнет)
            'wm'            =>  '0',          // Сумма которая была начисленна вебмастеру
            'rev'           =>  '0',          // Сумма которую получил RevNet
            't3'            =>  '0',          // Сумма которая осталась в партнерке
            
            // Только для лидов, которые были проданы нам
            'rtotal'        =>  $LeadRevnet_wm+0, // Сумма за которую был продан лид ревнету
            'revnet_percent'=>  '0',            // Процент который взял RevNet
        );
        
        self::$currentResult['data_email'] = self::getRLead()->data_email;
        
        if(self::$currentResult['status'] == 'success'){
            // проверка типа
            if(in_array($type, array(self::trackType_T3LeadsSold, self::trackType_OtherSold))){
                self::$currentResult['type'] = $type;              
            }
            else {
                self::setError('Invalid Track Type');    
            }
        }
        
        if(self::$currentResult['status'] == 'success'){
            // проверка цены
            if(is_numeric($price) && $price != 0){
                self::$currentResult['getPrice'] = round($price, 2);                             
            }
            else {
                self::setError('Price is Required');   
            }
        }
        
        if(self::$currentResult['status'] == 'success'){
            self::runTrack();       
        }
        
        // Сохранение трека
        T3Db::api()->insert('revnet_track', self::$currentResult);
        T3Db::api()->insert('revnet_track_inlog', array(
            'id'    => T3Db::api()->lastInsertId(),
            'inLog' => $inLog,
        ));
    }
    
    /**
    * @return T3RevNet_Lead
    */
    static protected function getRLead(){
        return T3RevNet_Leads::getLead(self::$currentResult['leadId']);    
    }
    
    /**
    * Процент который мы забираем себе при покупке лидов от ревнет, как от вебмастера
    * НАДО ПЕРЕДЕЛАТЬ НАДО ДИНАМИЧЕСКИЙ ВАРИАНТ ПОЛУЧЕНИЯ ЭТОГО ПРОЦЕНТА
    */
    static protected function getAdminPersentIfT3Sold(){
        return 0.2;    
    }
    
    static protected function runTrack(){
        // расчленение цены
        $per_wm  = self::getRLead()->per_wm;
        $per_rev = self::getRLead()->per_rev; 
        $per_adm = 1 - $per_wm - $per_rev;
        
        if(self::isType_OtherSold()){
            // Цена которую прислал Revnet делится между админом и вебмастером
            /*
            self::$currentResult['wm']  = round( (self::$currentResult['getPrice'] * $per_wm) / ($per_wm + $per_adm), 2);
            self::$currentResult['rev'] = round( (self::$currentResult['getPrice'] * $per_rev) / ($per_wm + $per_adm), 2);
            self::$currentResult['t3']  = round( self::$currentResult['getPrice'] - self::$currentResult['wm'], 2);
            */
            self::$currentResult['wm']  = round(self::$currentResult['getPrice'] * $per_wm, 2);
            self::$currentResult['rev'] = round((self::$currentResult['getPrice']*$per_rev)/(1-$per_rev), 2);
            self::$currentResult['t3']  = self::$currentResult['getPrice'] - self::$currentResult['wm'];
            
            self::$currentResult['revnet_percent'] = $per_rev*100;
            self::$currentResult['rtotal'] = round((self::$currentResult['getPrice'])/(1-$per_rev), 2); 
        }                               
        else if(self::isType_T3LeadsSold()){
            // Цена полностью отдается вебмастеру, при учете того что админский процент равен стандартному
            // В противном случае, админские денги будут компинировать разницу в большую или в меньшую сторону.
            
            /*
            $ttl = self::$currentResult['getPrice'] / (1 - $per_rev - self::getAdminPersentIfT3Sold());
            $addT3  = ($per_adm - self::getAdminPersentIfT3Sold()) * $ttl; // разница, которая может получится при нестандартный процентах
            
            self::$currentResult['wm']  = round( self::$currentResult['getPrice'] - $addT3, 2);
            self::$currentResult['rev'] = round( $per_rev * $ttl , 2);  
            self::$currentResult['t3']  = round( ($per_adm * $ttl) + $addT3 , 2);
            */
            self::$currentResult['wm']  = round(self::$currentResult['getPrice'] * $per_wm, 2); 
            self::$currentResult['rev'] = round((self::$currentResult['getPrice']*$per_rev)/(1-$per_rev), 2);
            self::$currentResult['t3']  = self::$currentResult['getPrice'] - self::$currentResult['wm'];
            
            if(self::$currentResult['rtotal'] != 0){
                self::$currentResult['revnet_percent'] = round((1 - (self::$currentResult['getPrice']/self::$currentResult['rtotal']))*100);
                self::$currentResult['rev'] = self::$currentResult['rtotal'] - self::$currentResult['getPrice']; 
            }
        }                                                                                            
        
        // создание бонуса вебмастеру
        if(self::$currentResult['wm'] != 0){
            $bonus = new T3Bonus();
            
            $bonus->webmaster_id    = self::getRLead()->webmaster;
            $bonus->action_sum      = self::$currentResult['wm'];
            $bonus->action_datetime = date("Y-m-d H:i:s");
            $bonus->lead_id         = self::getRLead()->lead_id;
            $bonus->from_old_system = 0;
            $bonus->comment         = "Other Bonus";
            
            $bonus->make();
        }
        
        // Изменение RЛида  
        /** @var T3RevNet_Lead */
        $total_for_stat = round(self::$currentResult['rev'] + self::$currentResult['wm'] + self::$currentResult['t3'], 2);
        
        $rlead = self::getRLead();
        T3Db::api()->update("revnet_leads", array(
            'rev'       => new Zend_Db_Expr("`rev`+" . self::$currentResult['rev']),
            'wm'        => new Zend_Db_Expr("`wm`+" .  self::$currentResult['wm']), 
            'ttl'       => new Zend_Db_Expr("`ttl`+" . $total_for_stat), 
            'get_price' => new Zend_Db_Expr("`get_price`+" . self::$currentResult['getPrice']), 
        ), "id={$rlead->id}");
        
        // Запись в кеш
        if($rlead->get_price == 0 && self::$currentResult['getPrice'] > 0){
            $countSold = 1;        
        }
        else if($rlead->get_price > 0 && round($rlead->get_price + self::$currentResult['getPrice'], 2) <= 0 ) {
            $countSold = -1;        
        }
        else {
            $countSold = 0;       
        }
        
        T3RevNet_Cache::soldLead(
            $rlead, 
            $countSold, 
            self::$currentResult['rev'], 
            self::$currentResult['wm'], 
            $total_for_stat, 
            self::$currentResult['getPrice']
        );
    } 
    
    static public function renderXML(){
        $xml = new DOMDocument("1.0","UTF-8"); 
        
        $root = $xml->createElement('Response');
        $xml->appendChild($root);
        
        $root->appendChild( $xml->createElement('Status', self::$currentResult['status']) ); // Статус
        $root->appendChild( $xml->createElement('Price', self::$currentResult['getPrice']) ); // Price 
        
        
        if(self::$currentResult['reason'])           $root->appendChild( $xml->createElement('Reason',   self::$currentResult['reason'])   );  // Reason 
            
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;

        return $xml->saveXML($root);
    }   
}