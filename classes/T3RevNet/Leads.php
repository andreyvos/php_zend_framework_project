<?php

class T3RevNet_Leads {
    const account_SoldHigh              = 'soldHigh';
    const account_SoldLow               = 'soldLow';
    const account_NotSold               = 'notSold';   
    const account_NotSoldDup            = 'notSoldDup';
    const account_NotSoldTime           = 'notSoldTime';     
    const account_NotSoldOnlyPhone      = 'notSoldOnlyPhone';
    const account_ServerPost            = 'serverPost';
    
    const account_UKPaydayServerPost    = 'UKPaydayServerPost';
    const account_UKPaydayLeads         = 'UKPaydayLeads';
    const account_UKPaydayNotSold       = 'UKPaydayNotSold';
    const account_UKPaydayNotSoldDup    = 'UKPaydayNotSoldDup';
    const account_UKPaydayNotSoldTime   = 'UKPaydayNotSoldTime';

    const account_CanadaServerPost      = 'CanadaServerPost';
    const account_CanadaLeads           = 'CanadaLeads';
    const account_CanadaNotSold         = 'CanadaNotSold';
    const account_CanadaNotSoldDup      = 'CanadaNotSoldDup';
    const account_CanadaNotSoldTime     = 'CanadaNotSoldTime';

    const account_UsaPersonalLoanServerPost     = 'UsaPersonalLoanServerPost';
    const account_UsaPersonalLoanLeads          = 'UsaPersonalLoanLeads';
    const account_UsaPersonalLoanNotSold        = 'UsaPersonalLoanNotSold';
    const account_UsaPersonalLoanNotSoldDup     = 'UsaPersonalLoanNotSoldDup';
    const account_UsaPersonalLoanNotSoldTime    = 'UsaPersonalLoanNotSoldTime';
    
    static protected $isNotSoldFinished = false;
    
    static public function getDuplicateDays($accountType){
        return T3RevNet_Settings::getAccountSetting($accountType, 'duplicateDays');   
    }
    
    static public function getAllAccounts(){
        return array(
            self::account_SoldHigh,
            self::account_SoldLow,
            self::account_NotSold,
            self::account_NotSoldDup,
            self::account_NotSoldTime,
            self::account_NotSoldOnlyPhone,
            self::account_ServerPost,
            
            self::account_UKPaydayServerPost,
            self::account_UKPaydayLeads,
            self::account_UKPaydayNotSold,
            self::account_UKPaydayNotSoldDup,
            self::account_UKPaydayNotSoldTime,

            self::account_CanadaServerPost,
            self::account_CanadaLeads,
            self::account_CanadaNotSold,
            self::account_CanadaNotSoldDup,
            self::account_CanadaNotSoldTime,

            self::account_UsaPersonalLoanServerPost,
            self::account_UsaPersonalLoanLeads,
            self::account_UsaPersonalLoanNotSold,
            self::account_UsaPersonalLoanNotSoldDup,
            self::account_UsaPersonalLoanNotSoldTime,
        );    
    }
    
    static protected $leadsCache = array();
    
    /**
    * Получение лида
    * 
    * @param int $id
    * @return T3RevNet_Lead
    */
    static public function getLead($id){
        if(!isset(self::$leadsCache[$id])){
            self::$leadsCache[$id] = new T3RevNet_Lead();
            self::$leadsCache[$id]->fromDatabase($id);     
        }
        return self::$leadsCache[$id];   
    }
    
    static public function isDuplicate($accountType, $email, $phone){
        if(
            strlen($email) &&
            T3Db::api()->fetchOne(
                "select id from revnet_leads where data_email = ? and create_date > ? limit 1", array(
                    $email,
                    date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - self::getDuplicateDays($accountType, date("Y")))) 
                )
            )
        ){
            return true;    
        }
        
        return false;   
    }
    
    /**********************************************************
    * USA
    ***************/
    
    static public function eventSoldHigh(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_SoldHigh);    
    }
    
    static public function eventSoldLow(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_SoldLow);    
    }
    
    static public function eventNotSold(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_NotSold);
    }
    
    static public function eventNotSoldDup(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_NotSoldDup);
    }
    
    static public function eventNotSoldTime(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_NotSoldTime);
    } 

    static public function eventNotSoldOnlyPhone(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_NotSoldOnlyPhone);
    }
    
    static public function eventSeverPost(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_ServerPost);    
    }
    
    /**********************************************************
    * UK
    ***************/
    
    static public function eventUKPaydaySeverPost(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UKPaydayServerPost);    
    }
    
    static public function eventUKPaydayLeads(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UKPaydayLeads);    
    }
    
    static public function eventUKPaydayNotSold(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UKPaydayNotSold);
    }
    
    static public function eventUKPaydayNotSoldDup(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UKPaydayNotSoldDup);
    }
    
    static public function eventUKPaydayNotSoldTime(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UKPaydayNotSoldTime);
    }

    /**********************************************************
     * Canada
     ***************/

    static public function eventCanadaSeverPost(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_CanadaServerPost);
    }

    static public function eventCanadaLeads(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_CanadaLeads);
    }

    static public function eventCanadaNotSold(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_CanadaNotSold);
    }

    static public function eventCanadaNotSoldDup(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_CanadaNotSoldDup);
    }

    static public function eventCanadaNotSoldTime(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_CanadaNotSoldTime);
    }

    /**********************************************************
     * Usa Pesonal Loan
     ***************/

    static public function eventUsaPersonalLoanSeverPost(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UsaPersonalLoanServerPost);
    }

    static public function eventUsaPersonalLoanLeads(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UsaPersonalLoanLeads);
    }

    static public function eventUsaPersonalLoanNotSold(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UsaPersonalLoanNotSold);
    }

    static public function eventUsaPersonalLoanNotSoldDup(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UsaPersonalLoanNotSoldDup);
    }

    static public function eventUsaPersonalLoanNotSoldTime(T3Lead $lead){
        return self::event($lead, T3RevNet_Leads::account_UsaPersonalLoanNotSoldTime);
    }

    /*********************************************************/
    
    static protected $description = '';

    /**
     * Проверить можно ли посылать на данный revnet канал этот лид
     *
     * @param T3Lead $lead
     * @param $accountType
     * @return bool
     */
    static public function filterWebmasters(T3Lead $lead, $accountType){
        // Фильтрация вебмастреов
        $rule = T3Db::api()->fetchRow(
            "select post from revnet_settings where account=? and (channel_type='all' or channel_type=?) " .
            "and (webmaster='0' or webmaster=?) order by webmaster desc, channel_type desc limit 1",
            array($accountType, $lead->get_method, $lead->affid)
        );

        if(is_array($rule)){
            if($rule['post'] == '0'){
                // по правилу поста быть не должно
                return false;
            }
        }
        else {
            // правила не найденно
            return false;
        }

        return true;
    }

    static protected function event(T3Lead &$lead, $accountType){
        /**
        * 1. проверка EventType
        * 2. Фильтрация вебмастреов 
        * 3. Проверка максимального количесва постов в день
        * 4. Проврка на бупликат
        * 5. Создание лида
        * 6. Отправка      
        */
        
        self::$description = '';
        
        // проверка EventType
        if(!in_array($accountType, self::getAllAccounts())){
            // неизвестный тип аккаунта
            self::$description = 'Invalid Account Type';
            return null;    
        } 
        
        // Фильтрация вебмастреов
        $rule = T3Db::api()->fetchRow(
            "select percentWM,persentRevnet,post,days from revnet_settings where account=? and (channel_type='all' or channel_type=?) " .
            "and (webmaster='0' or webmaster=?) order by webmaster desc, channel_type desc limit 1",
            array($accountType, $lead->get_method, $lead->affid)
        );

        if(is_array($rule)){
            if($rule['post'] == '0'){
                // по правилу поста быть не должно
                return null;
            }
        }
        else {
            // правила не найденно
            return null;
        }

        // Проверка максимального количесва постов в день 
        // Отложенно потосучто непонятно надо это или нет
        
        // Проврка на дупликат
        /*
        if(self::isDuplicate($accountType, $lead->data_email, $lead->data_phone)){
            // Дупликат
            return null;   
        } 
        */
        
        // Защита от перепоста для связки: NotSold - NotSoldDup - NotSoldTime
        if(self::$isNotSoldFinished == true){
            return null;   
        }
          
        if(
            $accountType == self::account_NotSold || 
            $accountType == self::account_NotSoldOnlyPhone ||
            $accountType == self::account_NotSoldTime ||

            $accountType == self::account_UKPaydayNotSold ||
            $accountType == self::account_UKPaydayNotSoldTime ||

            $accountType == self::account_CanadaNotSold ||
            $accountType == self::account_CanadaNotSoldTime ||

            $accountType == self::account_UsaPersonalLoanNotSold ||
            $accountType == self::account_UsaPersonalLoanNotSoldTime
        ){  
            self::$isNotSoldFinished = true;
        }
        else if(
            $accountType == self::account_NotSoldDup || 
            $accountType == self::account_UKPaydayNotSoldDup ||
            $accountType == self::account_CanadaNotSoldDup ||
            $accountType == self::account_UsaPersonalLoanNotSoldDup
        ){
            // Проверить был ли лид с таким мыльцем в системе от другого вебмастера
            $is = (bool)T3Db::api()->fetchOne(
                "select id from leads_data where data_email=? and (affid!=? || wm > 0) order by id desc limit 1",
                array(
                    $lead->data_email,
                    $lead->affid
                )
            );
            
            if($is){
                self::$isNotSoldFinished = true; 
            }
            else {
                // такого лида не было в системе и его нельзя постать
                return null;
            }  
        }  
        
        /**
        * Создание лида
        * @var T3RevNet_Lead
        */
        $revnet_lead = new T3RevNet_Lead();
        
        $revnet_lead->create_date   = date('Y-m-d H:i:s');
        $revnet_lead->webmaster     = $lead->affid; 
        $revnet_lead->product       = $lead->product; 
        $revnet_lead->lead_id       = $lead->id;
        $revnet_lead->account       = $accountType;
        $revnet_lead->data_email    = $lead->data_email;
        $revnet_lead->data_phone    = $lead->data_phone; 
        $revnet_lead->per_rev       = T3RevNet_Settings::getAccountSetting($accountType, 'revnetPersent'); 
        $revnet_lead->per_wm        = $rule['percentWM'];
        $revnet_lead->insertIntoDatabase(); 
        
        if(
            $accountType == self::account_NotSoldTime || 
            $accountType == self::account_UKPaydayNotSoldTime ||
            $accountType == self::account_CanadaNotSoldTime ||
            $accountType == self::account_UsaPersonalLoanNotSoldTime
        ){
            try{
                T3Db::api()->insert("revnet_time_days", array(
                    'id'    => $revnet_lead->id,
                    'days'  => $rule['days'], 
                )); 
            }
            catch(Exception $e){
                
            }
        }
        
        // Отправка
        //$revnet_lead->post();
        /*if($accountType != self::account_NotSoldTime)*/ $revnet_lead->postAsync(); // пока не потяно как постать NotSoldTime
        
        T3RevNet_Cache::addPost($revnet_lead);
        
        return true;
    } 
    
    static public function sendPotencial($maxCount = 100, $waitSeconds = 600){
        $leads = T3Db::api()->fetchCol("select leadid from revnet_potential_notsold where UNIX_TIMESTAMP(create_time) < UNIX_TIMESTAMP(NOW())-{$waitSeconds} limit {$maxCount}");
        
        if(count($leads)){
            T3Db::api()->delete("revnet_potential_notsold", "`leadid` in (" . implode(",", $leads) . ")");
            
            foreach($leads as $id){
                $lead = new T3Lead();
                $lead->fromDatabase($id);  
                
                if($lead->id && !$lead->isStatusSold()){ 
                    self::eventNotSold($lead);
                }
            }
        }            
    }
    
    static public function addPotentialNotSold(T3Lead $lead){
        // Фильтрация вебмастреов
        $rule = T3Db::api()->fetchRow(
            "select percentWM,persentRevnet,post from revnet_settings where account=? and (channel_type='all' or channel_type=?) " . 
            "and (webmaster='0' or webmaster=?) order by webmaster desc, channel_type desc limit 1", 
            array(T3RevNet_Leads::account_NotSold, $lead->get_method, $lead->affid)
        );
        if(is_array($rule)){
            if($rule['post'] == '0'){
                // по правилу поста быть не должно
                return null;  
            }        
        }
        else {
            // правила не найденно
            return null;      
        } 
        
        T3Db::api()->insert("revnet_potential_notsold", array(
            'leadid'        => $lead->id,
            'create_time'   => new Zend_Db_Expr("NOW()"),
        )); 
    }   
}