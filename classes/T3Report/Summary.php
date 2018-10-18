<?php

/*
есть функции:
1. Получен новый лид
2. Изменение цены лида


Отвечает на вопросы:
1. Какие продукты использовались за период времени

*/

class T3Report_Summary {   
    private $system;
    private $database;
    
    function __construct(){
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect(); 
    }
    
    /**
     * Новый ADS клик
     *
     * @param int $webmasterID
     * @param int $subAccountID
     * @param string $product
     */
    static public function addNewAdsClick($webmasterID, $subAccountID, $product){
        $lead               = new T3Lead();
        $lead->affid        = $webmasterID;
        $lead->datetime     = date('Y-m-d H:i:s');
        $lead->product      = $product;
        $lead->get_method   = 'js_form';
        $lead->channel_id   = 1;
        $lead->agentID      = T3WebmasterCompanys::getAgentID($webmasterID);


        // Кеш сумарной дневной статы 
        self::cacheForWebmasterMainRun($lead, array(
            array('adClicks',    1),     
        ));  
    }

    /**
     * Оплата за ADS
     *
     * @param int $datetime
     * @param int $webmasterID
     * @param string $product
     * @param $channelID
     * @param T3PriceRule $PriceRule
     *
     * @internal param float $value
     */
    static public function addAdsPay($datetime, $webmasterID, $product, $channelID, T3PriceRule $PriceRule){
        $lead               = new T3Lead();
        $lead->affid        = $webmasterID;
        $lead->datetime     = $datetime;
        $lead->product      = $product;
        $lead->get_method   = 'js_form';
        $lead->channel_id   = $channelID;
        $lead->agentID      = T3WebmasterCompanys::getAgentID($webmasterID);    
        
        // Кеш сумарной дневной статы 
        self::cacheForWebmasterMainRun($lead, array(
            array('moneyAD',            $PriceRule->priceWM),    
            array('moneyAgent',         $PriceRule->priceAgent),
            array('moneyTTL',           $PriceRule->priceTTL),
            array('moneyWMforRef',      $PriceRule->priceReferal),   
        ));  
    }

    static public function addSoraClient(T3Lead $lead, $soraKeyApi = null){
        /*
         * sora_leads
         * sora_tnx
         * sora_dup
         * money_sora_tnx_wm
         * money_sora_dup_wm
         * money_sora_ttl_wm
         * money_sora_tnx
         * money_sora_dup
         * money_sora_ttl
         */

        $add = array(array('sora_leads', 1));

        if($soraKeyApi == 'HGJrphYZ3wRrqQ'){
            // dup
            $add[] = array('sora_dup', 1);
        }
        else if($soraKeyApi == '2cpgveSTZ14rd8o'){
            // tnx
            $add[] = array('sora_tnx', 1);
        }

        self::cacheForWebmasterMainRun($lead, $add);
    }

    static public function addSoraPay(
        $soraKeyApi, $datetime, $webmaster_id, $channel_id, $subaccount_id, $product, $wm, $ttl
    ){
        $lead = new T3Lead();

        $lead->datetime     = $datetime;
        $lead->affid        = $webmaster_id;
        $lead->channel_id   = $channel_id;
        $lead->subacc       = $subaccount_id;
        $lead->product      = $product;

        $lead->agentID = T3WebmasterCompanys::getAgentID($lead->affid);

        $lead->get_method = 'js_form';
        $lead->is_mobile = 0; // TODO

        $add = array(
            array('money_sora_ttl_wm',  $wm),
            array('money_sora_ttl',     $ttl),
        );

        if($soraKeyApi == 'HGJrphYZ3wRrqQ'){
            // dup
            $add[] = array('money_sora_dup_wm', $wm);
            $add[] = array('money_sora_dup',    $ttl);
        }
        else if($soraKeyApi == '2cpgveSTZ14rd8o'){
            // tnx
            $add[] = array('money_sora_tnx_wm', $wm);
            $add[] = array('money_sora_tnx',    $ttl);
        }

        self::cacheForWebmasterMainRun($lead, $add);
    }
    
    /**
     * Новый клик
     *
     * @param T3Channel_JsFormUniqueClient $click
     */
    static public function addNewClick(T3Channel_JsFormUniqueClient $click){
        /*
            1. Кеш сумарной дневной статы  -OK!
        */
        if($click->webmaster && $click->webmaster != 3000){
            if(!$click->channel_id) $click->channel_id = 1;
            
            $lead               = new T3Lead();
            $lead->affid        = $click->webmaster;
            $lead->datetime     = $click->date;
            $lead->product      = $click->product;
            $lead->get_method   = $click->channel_type;
            $lead->channel_id   = $click->channel_id;
            $lead->agentID      = $click->agent; 
            $lead->is_mobile    = $click->isMobile; //Added
            
            // Кеш сумарной дневной статы 
            if($click->version == '1'){
                self::cacheForWebmasterMainRun($lead, array(
                    array('uniqueClicks_v1', 1), 
                    array('uniqueClicks',    1),  
                ));    
            }
            else {
                T3Log_Webmaster_Summary_Report::clickNew($lead);


                self::cacheForWebmasterMainRun($lead, array(
                    array('uniqueClicks',    1),     
                ));
            }
            
            if (strlen($click->subaccount)){
                T3SubaccountReport::addClick($click);
            }
        }   
    }

    /**
     * Поступил новый лид
     *
     * @param T3Lead $lead
     * @param bool $fromV1
     */
    static public function addNewLead(T3Lead $lead, $fromV1 = false){
        /*
            1. Кеш сумарной дневной статы -OK!
            2. Кеш сабаккаунтов  -OK!
            3. Трактинг -OK!
        */

        if($lead->subacc>0){
            T3SubaccountReport::updateCacheLead($lead->subacc, date("Y-m-d", strtotime($lead->datetime)), 1, 0, 0);
        }

        // Тракинг
        if(!$fromV1){
            T3Tracking_Items::addLead($lead);
        }

        // Кеш сумарной дневной статы
        if($fromV1){
            self::cacheForWebmasterMainRun($lead, array(
                array('all_leads', 1),
                array('all_leads_v1', 1)
            ));
        }
        else {
            // проверка на уникальность (в кеш осядет информация сколько было похожих лидов за день, неделю, 2 недели, месяц)
            $dupAll = T3Db::api()->fetchCol(
                "select `date` from buyer_channels_dup_email_global where email=? and product=? and `date` > ? order by id desc",
                array(
                    $lead->data_email,
                    T3Products::getID($lead->product),
                    date('Y-m-d', mktime(0, 0, 0, date('m') - 1, date('d') - 1, date('Y')))
                )
            );

            $periods = array(
                'day'    => array(0, -1),  // 1 day
                'week'   => array(0, -7),  // 1 week
                '2weeks' => array(0, -14), // 2 weeks
                'month'  => array(-1, 0),  // 1 month
            );

            $result = array();

            foreach($periods as $k => $v){
                $result[$k] = "1";

                if(count($dupAll)){
                    $min = mktime(0, 0, 0, date('m') + $v[0], date('d') + $v[1], date('Y'));

                    foreach($dupAll as $el) {

                        if(strtotime($el) > $min){
                            $result[$k] = "0";
                        }
                    }
                }
            }

            // запрос на запись в базу
            self::cacheForWebmasterMainRun($lead, array(
                array('all_leads', 1),
                array('unique_leads',       $result['day']),
                array('unique_leads_week',  $result['week']),
                array('unique_leads_2week', $result['2weeks']),
                array('unique_leads_month', $result['month']),
            ));

            T3Log_Webmaster_Summary_Report::leadNew(
                $lead,
                $result['day'],
                $result['week'],
                $result['2weeks'],
                $result['month']
            );
        }
    }
    
    

    /**
    * Пришло измененеи баланса вебмасетра из старой системы  
    *
    * @param int $webmasterID
    * @param float $money
    * @param string $date
    */
    static public function addBalance($webmasterID, $money, $date){
        // Сейчас не используется !!!!!!
        
        $agentid = T3System::getConnect()->fetchOne('select agentID from users_company_webmaster where id=?', $webmasterID);        

        $lead = new T3Lead();

        $lead->agentID = $agentid;
        $lead->affid = $webmasterID;
        $lead->datetime = $date;
        $lead->product = "v1balance";
        $lead->get_method = "other";
        $lead->channel_id = "1";

        self::cacheForWebmasterMainRun(
            $lead,
            array(
                array('moneyWM',    $money),
                array('moneyTTL',   $money),
            )
        ); 
    }

    /**
     * Цена лида изменилась, по причине продажи
     *
     * @param mixed $soldLead      1  -  (+1 к проданным лидам) ранее цена лида была равна нулю,
     *                             0  -  (количество проданных лидов не изменилось) лид был проданным, ну были изменения в цене после которых лид остался проданным
     *                            -1  -  (На 1 проданный лид стало меньше) Полный реджект лида, лид был проданным, а после изменения цены он стал стоить 0!
     * @param T3Lead $lead
     * @param T3BuyerChannel $buyerChannel
     * @param T3PriceRule $PriceRule
     * @param bool $fromV1
     */
    static public function addPayLead(
        $soldLead,
        T3Lead $lead,
        T3BuyerChannel $buyerChannel, 
        T3PriceRule $PriceRule,
        $fromV1 = false
    ){
        /*
            1. Кеш сумарной дневной статы  -OK!
            2. Кеш сабаккаунтов -OK! 
            3. тракинг -OK!
            4. Обновление данных в Visitors -OK!
        */        
        if ($lead->subacc>0){
            T3SubaccountReport::updateCacheLead($lead->subacc, date("Y-m-d", strtotime($lead->datetime)), 0, $soldLead, $PriceRule->priceWM);
        }
        
        // Здесь можно будет обновлять информацию в lead_visitors о деньгах
        if (false){
            T3Visitors::updateEarnings($lead->id, $lead->wm, $lead->agn, $lead->ref, $lead->ttl);
        }
        
        // если $buyerChannel->id = null, знаит неизвестно какой канал (с синхронизации)
        
        // Кеш сумарной дневной статы 
        if($fromV1){
            self::cacheForWebmasterMainRun(
                $lead,
                array(
                    array('sold_leads_v1',      $soldLead),
                    array('moneyWM_v1',         $PriceRule->priceWM),
                    array('moneyAgent_v1',      $PriceRule->priceAgent),
                    array('moneyTTL_v1',        $PriceRule->priceTTL),
                    array('moneyWMforRef_v1',   $PriceRule->priceReferal),
                    
                    array('sold_leads',         $soldLead),
                    array('moneyWM',            $PriceRule->priceWM),
                    array('moneyAgent',         $PriceRule->priceAgent),
                    array('moneyTTL',           $PriceRule->priceTTL),
                    array('moneyWMforRef',      $PriceRule->priceReferal),
                )
            ); 
        } 
        else {
            self::cacheForWebmasterMainRun(
                $lead,
                array(
                    array('sold_leads',         $soldLead),
                    array('moneyWM',            $PriceRule->priceWM),
                    array('moneyAgent',         $PriceRule->priceAgent),
                    array('moneyTTL',           $PriceRule->priceTTL),
                    array('moneyWMforRef',      $PriceRule->priceReferal),
                )
            );

            T3Log_Webmaster_Summary_Report::leadPay(
                $lead,
                $soldLead,
                $PriceRule->priceWM,
                $PriceRule->priceReferal,
                $PriceRule->priceAgent,
                $PriceRule->priceTTL
            );
        }  
        
        // Тракинг
        if(!$fromV1 && $PriceRule->priceWM){
            T3Tracking_Items::changeLeadPrice($lead, $PriceRule->priceWM);  
        } 
    }
    
    /**
    * Прошел ретурн  
    * 
    * @param T3Lead_Return $return
    */
    static public function addNewReturn(
        T3Lead_Return $return
    ){
        
        /*
            1. Кеш сумарной дневной статы  -OK! 
            2. Вычет с реферальной программы только для ретурнов для лидов из новой системы  -OK! 
            3. Трактинг -OK!
        */
        $lead               = new T3Lead();
        $lead->affid        = $return->affid;
        $lead->datetime     = $return->return_datetime;
        $lead->product      = $return->product;
        $lead->get_method   = $return->get_method;
        $lead->channel_id   = $return->channel_id;
        $lead->agentID      = $return->agentID;    
        
        // Кеш сумарной дневной статы 
        if($return->from_v1){
            self::cacheForWebmasterMainRun(
                $lead,
                array(
                    array('return_leads_v1',    1),
                    array('moneyWMReturns_v1',  $return->wm),
                    array('moneyAgent_v1',      $return->agn), 
                    array('moneyTTL_v1',        $return->ttl),
                    array('moneyWMforRef_v1',   $return->ref),
                    
                    array('return_leads',       1),
                    array('moneyWMReturns',     $return->wm),
                    array('moneyAgent',         $return->agn), 
                    array('moneyTTL',           $return->ttl),
                    array('moneyWMforRef',      $return->ref), 
                )
            );    
        }
        else {
            self::cacheForWebmasterMainRun(
                $lead,
                array(
                    array('return_leads',       1),
                    array('moneyWMReturns',     $return->wm),
                    array('moneyAgent',         $return->agn), 
                    array('moneyTTL',           $return->ttl), 
                    array('moneyWMforRef',      $return->ref),
                )
            );

            T3Log_Webmaster_Summary_Report::leadReturn(
                $lead,
                $return->wm,
                $return->ref,
                $return->agn,
                $return->ttl
            );
        } 
        
        if(!$return->from_v1 && $return->refaffid && $lead->ref != 0){
            $lead->refaffid     = $return->refaffid;
            
            $PriceRule = new T3PriceRule();
            $PriceRule->priceReferal = $lead->ref;
            
            self::addReferalPay($lead, $PriceRule);
        }
        
        // Тракинг
        if(!$return->from_v1){
            T3Tracking_Items::addReturn($return);  
        }
        
            
    }

    static public function addNewBonus(T3Bonus $bonus){
        /*
            1. Кеш сумарной дневной статы  -OK! 
            2. Трактинг -OK!
        */
        
        // Кеш сумарной дневной статы
        $lead               = new T3Lead();
        $lead->affid        = $bonus->webmaster_id;
        $lead->datetime     = $bonus->action_datetime;
        $lead->product      = $bonus->lead_product;
        $lead->get_method   = empty($bonus->lead_get_method) ? 'other' : $bonus->lead_get_method;
        $lead->channel_id   = empty($bonus->lead_channel_id) ? 1 : $bonus->lead_channel_id;
        $lead->agentID      = T3WebmasterCompanys::getAgentID($bonus->webmaster_id);

        if(!$bonus->from_old_system){
            self::cacheForWebmasterMainRun($lead,array(
                array('bonuses_count', 1),
                array('moneyBonuses', $bonus->action_sum),
            ));

            T3Log_Webmaster_Summary_Report::bonusNew(
                $lead,
                $bonus->action_sum
            );
        }
        else {
            self::cacheForWebmasterMainRun($lead,array(
                array('bonuses_count',   1),
                array('bonuses_count_v1', 1),
                array('moneyBonuses',     $bonus->action_sum),
                array('moneyBonuses_v1',  $bonus->action_sum),
            ));
        }
        
        // Тракинг
        T3Tracking_Items::addBonus($bonus); 

    }

    /**
     * Начисления по реф. программе
     *
     * @param T3Lead $lead
     * @param T3PriceRule $PriceRule
     * @param bool $fromV1
     */
    static public function addReferalPay(T3Lead $lead, T3PriceRule $PriceRule, $fromV1 = false ){
        /*
            1. Кеш сумарной дневной статы -OK!
            2. Кеш реферальных начислений -OK!
        */
        
        // Кеш сумарной дневной статы 
        if(isset($lead->refaffid) && $lead->refaffid){
            
            // содания нового объекта псевдо лида, для кеширования данных для рефера
            $lead_ref = new T3Lead($lead->product);
            $lead_ref->affid = $lead->refaffid;
            $lead_ref->datetime = $lead->datetime;
            $lead_ref->agentID = T3WebmasterCompanys::getCompany($lead->refaffid)->agentID; 
            
            if($fromV1){ 
                self::cacheForWebmasterMainRun(
                    $lead_ref, 
                    array(
                        array('moneyRef_v1', $PriceRule->priceReferal),
                        array('moneyRef', $PriceRule->priceReferal),   
                    ), 
                    false // не записывать в детальный кеш
                );   
            }
            else {
                self::cacheForWebmasterMainRun(
                    $lead_ref, 
                    array(
                        array('moneyRef', $PriceRule->priceReferal),   
                    ), 
                    false // не записывать в детальный кеш
                );


            }
        } 
    }
    
    /**
    * Специфальная функция для апдейта таблиц общего ккеша лидов для вебмастеров
    * 
    * @param mixed $format_insert
    * @param mixed $format_update
    * @param mixed $table
    * @param mixed $var
    * @param mixed $val
    */
    static protected function dbForWebmasterInsertUpdate($format_insert,$format_update,$table,$var,$val){
        $database = T3Db::api();

        /*
        if($table == 'cache_summary_hours_details_all'){
            T3Db::test()->insert("test", array(
                'text' =>
                    var_export(T3Db::api()->fetchRow("SELECT @@global.time_zone, @@session.time_zone"), 1) .
                    sprintf($format_update, $table, $var, $database->quote($val))
            ));
        }
        */

        try {                  $database->query(  sprintf($format_insert, $table, $var, $database->quote($val))  );  }
        catch(Exception $e) {  $database->query(  sprintf($format_update, $table, $var, $database->quote($val))  );  }    
    }

    static public function cacheForWebmasterMainRun(T3Lead $lead, $action, $detailsRun = true){
        $webmasterID    =   ifset($lead->affid,null);
        $date           =   substr($lead->datetime,0,10);
        $product        =   ifset($lead->product,null);

        $channelType    =   ifset($lead->get_method,null);

        // Если этот лид пришел с мобильной версии формы, то подменяем $channelType
        if($channelType == 'js_form' && $lead->is_mobile){
            $channelType = "mobile_form";
        }

        $channelID      =   ifset($lead->channel_id,null);

        if(!$channelID)$channelID = "1";

        $agentID        =   ifset($lead->agentID,null);
        $dateFotHours = substr($lead->datetime, 0, 13) . ":00:00";

        if(is_null($date) || strlen($date)==0){
            $date = date("Y-m-d");
            $dateFotHours = date("Y-m-d H") . ":00:00";
        }

        /*
        if(false){
            T3Db::api()->insert("cache_summary_log", array(
                'webmasterID'  => $webmasterID,
                'date'         => $date,
                'product'      => $product,
                'channelType'  => $channelType,
                'channelID'    => $channelID,
                'agentID'      => $agentID,
                'dateFotHours' => $dateFotHours,
                'action'       => serialize($action),
                'detailsRun'   => (int)$detailsRun,
            ));
        }
        else {
        */
            self::cacheForWebmasterMainRun_Go(
                $webmasterID, $date, $product, $channelType, $channelID, $agentID, $dateFotHours, $action, $detailsRun
            );
        /*
        }
        */
    }

    /**
     * Выполнить сохранение в таюлицы в базе данных
     *
     *      функция добавленна для того что бы иметь возможность временно отклчать
     *      сохрарение в кешевые таблицы для изменения их структуры или добавления индексов
     *      при этом сохранять лог изменений, а потом доливать изменения в кешевые таблицы)
     *
     * @param $webmasterID
     * @param $date
     * @param $product
     * @param $channelType
     * @param $channelID
     * @param $agentID
     * @param $dateFotHours
     * @param $action
     * @param $detailsRun
     */
    static public function cacheForWebmasterMainRun_Go(
        $webmasterID, $date, $product, $channelType, $channelID, $agentID, $dateFotHours, $action, $detailsRun
    ){
        // интерпритация переменной action, получение масства $real_action, в который записались все раельные дествия, которые нужно провести
        $real_action = array();
        if(is_string($action)){
            $real_action[] = array($action,'1');
        }
        else if(is_array($action) && isset($action[0]) && is_string($action[0])){
            if(isset($action[1])){
                $real_action[] = array($action[0],$action[1]);
            }
            else {
                $real_action[] = array($action[0],'1');
            }
        }
        else if(is_array($action) && isset($action[0]) && is_array($action[0])){
            foreach($action as $ac){
                if(is_string($ac)){
                    $real_action[] = array($ac,'1');
                }
                else if(is_array($ac) && isset($ac[0]) && is_string($ac[0])){
                    if(isset($ac[1])){
                        $real_action[] = array($ac[0],$ac[1]);
                    }
                    else {
                        $real_action[] = array($ac[0],'1');
                    }
                }
            }
        }

        if(count($real_action)){
            // формирование дополнительных строк с данными для запросов
            $sql_insert_var = "";
            $sql_insert_val = "";
            $sql_update = "";
            for($i=0;$i<count($real_action);$i++){
                $sql_insert_var.= ",`{$real_action[$i][0]}`";
                $sql_insert_val.= ",'{$real_action[$i][1]}'";
                $sql_update.= "`{$real_action[$i][0]}`=`{$real_action[$i][0]}`+{$real_action[$i][1]}";
                if($i!=count($real_action)-1)$sql_update.= ",";
            }

            // #### Высшее логирование, без дополнительных параметров
            // 1. DATE
            // 2. [USER|AGENT|GLOBAL]

            if($webmasterID){
                ///////////////////////////// Дневная /////////////////////////////////////////////
                $format_insert = "insert into `%s`(`%s`,`date`{$sql_insert_var}) values(%s,'{$date}'{$sql_insert_val})";
                $format_update = "update `%s` set {$sql_update} where `%s`=%s and `date`='{$date}'";

                // по пользователям
                self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days","userid",$webmasterID);

                // глобальное
                self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days_all","for","0");

                // высшее логирование по агентам когда
                if($agentID)    self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days_all","for",$agentID);  // АГЕНТ ЕСТЬ
                else            self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days_all","for","1");       // АГЕНТА НЕТ

                ///////////////////////////// Почасовая /////////////////////////////////////////////
                $format_insert = "insert into `%s`(`%s`,`datetime`{$sql_insert_var}) values(%s,'{$dateFotHours}'{$sql_insert_val})";
                $format_update = "update `%s` set {$sql_update} where `%s`=%s and `datetime`='{$dateFotHours}'";

                // по пользователям
                self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours","userid",$webmasterID);

                // глобальное
                self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours_all","for","0");

                // высшее логирование по агентам когда
                if($agentID)    self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours_all","for",$agentID);  // АГЕНТ ЕСТЬ
                else            self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours_all","for","1");       // АГЕНТА НЕТ
            }


            // #### детальное логирование по параметрам:
            // 1. DATE
            // 2. [USER|AGENT|GLOBAL]
            // 3. Product
            // 4. Channel Type
            // 5. Channel ID

            if($detailsRun && $webmasterID && $product && $channelType && $channelID){
                ///////////////////////////// Дневная /////////////////////////////////////////////
                $format_insert_user = "insert into `%s`(`%s`, `product`, `channel_type`, `channel_id`, `date`{$sql_insert_var}) values(%s,'{$product}','{$channelType}','{$channelID}','{$date}'{$sql_insert_val})";
                $format_update_user = "update `%s` set {$sql_update} where `%s`=%s and `date`='{$date}' and `product`='{$product}' and `channel_type`='{$channelType}' and channel_id='{$channelID}'";

                $format_insert = "insert into `%s`(`%s`, `product`, `channel_type`, `channel_id`, `date`{$sql_insert_var}) values(%s,'{$product}','{$channelType}','1','{$date}'{$sql_insert_val})";
                $format_update = "update `%s` set {$sql_update} where `%s`=%s and `date`='{$date}' and `product`='{$product}' and `channel_type`='{$channelType}' and channel_id='1'";

                // по пользователям
                self::dbForWebmasterInsertUpdate($format_insert_user,$format_update_user,"cache_summary_days_details","userid",$webmasterID);

                // глобальное
                self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days_details_all","for","0");

                // детальное логирование по агентам когда:
                if($agentID)    self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days_details_all","for",$agentID);  // АГЕНТ ЕСТЬ
                else            self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_days_details_all","for","1");       // АГЕНТА НЕТ

                ///////////////////////////// Почасовая /////////////////////////////////////////////
                $format_insert_user = "insert into `%s`(`%s`, `product`, `channel_type`, `channel_id`, `datetime`{$sql_insert_var}) values(%s,'{$product}','{$channelType}','{$channelID}','{$dateFotHours}'{$sql_insert_val})";
                $format_update_user = "update `%s` set {$sql_update} where `%s`=%s and `datetime`='{$dateFotHours}' and `product`='{$product}' and `channel_type`='{$channelType}' and channel_id='{$channelID}'";

                $format_insert = "insert into `%s`(`%s`, `product`, `channel_type`, `channel_id`, `datetime`{$sql_insert_var}) values(%s,'{$product}','{$channelType}','1','{$dateFotHours}'{$sql_insert_val})";
                $format_update = "update `%s` set {$sql_update} where `%s`=%s and `datetime`='{$dateFotHours}' and `product`='{$product}' and `channel_type`='{$channelType}' and channel_id='1'";

                // по пользователям
                self::dbForWebmasterInsertUpdate($format_insert_user,$format_update_user,"cache_summary_hours_details","userid",$webmasterID);

                // глобальное
                self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours_details_all","for","0");

                // детальное логирование по агентам когда:
                if($agentID)    self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours_details_all","for",$agentID);  // АГЕНТ ЕСТЬ
                else            self::dbForWebmasterInsertUpdate($format_insert,$format_update,"cache_summary_hours_details_all","for","1");       // АГЕНТА НЕТ
            }
        }
    }
    
    /**
     * Урезанная функция для обновления детального кеша вебмастера
     * Для реиндексации деталей вебмастра с целью исправления бага
     *
     * @param T3Lead $lead
     * @param mixed $action
     */
    static protected function cacheForWebmasterMainRun_OnlyUserDetails(T3Lead $lead, $action){ 
        
        $webmasterID    =   ifset($lead->affid,null);
        $date           =   substr($lead->datetime,0,10);
        $product        =   ifset($lead->product,null);
        $channelType    =   ifset($lead->get_method,null);
        $channelID      =   ifset($lead->channel_id,null);
        // $agentID        =   ifset($lead->agentID,null);
        
        if(is_null($date) || strlen($date)==0){
            $date = date("Y-m-d");
        }
        
        // интерпритация переменной action, получение масства $real_action, в который записались все раельные дествия, которые нужно провести
        $real_action = array();
        if(is_string($action)){
            $real_action[] = array($action,'1');        
        }
        else if(is_array($action) && isset($action[0]) && is_string($action[0])){
            if(isset($action[1])){
                $real_action[] = array($action[0],$action[1]); 
            }
            else {
                $real_action[] = array($action[0],'1');
            }    
        }
        else if(is_array($action) && isset($action[0]) && is_array($action[0])){
            foreach($action as $ac){
                if(is_string($ac)){
                    $real_action[] = array($ac,'1');        
                }
                else if(is_array($ac) && isset($ac[0]) && is_string($ac[0])){
                    if(isset($ac[1])){
                        $real_action[] = array($ac[0],$ac[1]); 
                    }
                    else {
                        $real_action[] = array($ac[0],'1');
                    }    
                }
            }   
        }
        
        if(count($real_action)){
            // формирование дополнительных строк с данными для запросов
            $sql_insert_var = "";
            $sql_insert_val = "";
            $sql_update = ""; 
            for($i=0;$i<count($real_action);$i++){
                $sql_insert_var.= ",`{$real_action[$i][0]}`";  
                $sql_insert_val.= ",'{$real_action[$i][1]}'"; 
                $sql_update.= "`{$real_action[$i][0]}`=`{$real_action[$i][0]}`+{$real_action[$i][1]}";
                if($i!=count($real_action)-1)$sql_update.= ",";              
            } 
            
            // #### детальное логирование по параметрам:
            // 1. DATE 
            // 2. USER  
            // 3. Product
            // 4. Channel Type
            // 5. Channel ID
            
            if($webmasterID && $product && $channelType && $channelID){
                $format_insert_user = "insert into `%s`(`%s`, `product`, `channel_type`, `channel_id`, `date`{$sql_insert_var}) values(%s,'{$product}','{$channelType}','{$channelID}','{$date}'{$sql_insert_val})";
                $format_update_user = "update `%s` set {$sql_update} where `%s`=%s and `date`='{$date}' and `product`='{$product}' and `channel_type`='{$channelType}' and channel_id='{$channelID}'";
                
                // по пользователям
                self::dbForWebmasterInsertUpdate($format_insert_user,$format_update_user,"cache_summary_days_details","userid",$webmasterID);
            } 
        }                 
    }      
    
    static protected function sumColumsArray($colums, $dateIndexName){
        $tempColums = array();
        $tempColums[] = $dateIndexName;
        foreach($colums as $el){
            $tempColums[] = new Zend_Db_Expr("sum(`{$el}`) as `{$el}`");    
        }
        
        return $tempColums;            
    }
           
    static public $columsMainReport = array(
        'adClicks',             'moneyAD',
        'uniqueClicks',         'uniqueClicks_v1',
        'all_leads',            'all_leads_v1', 
        'unique_leads',         'unique_leads_week',
        'unique_leads_2week',   'unique_leads_month',
        'sold_leads',           'sold_leads_v1', 
        'return_leads',         'return_leads_v1', 
        'bonuses_count',        'bonuses_count_v1',

        'sora_leads',           'sora_tnx',
        'sora_dup',

        'money_sora_tnx_wm',    'money_sora_dup_wm',
        'money_sora_ttl_wm',

        'money_sora_tnx',       'money_sora_dup',
        'money_sora_ttl',

        'moneyWMReturns',       'moneyWMReturns_v1', 
        'moneyBonuses',         'moneyBonuses_v1', 
        'moneyWM',              'moneyWM_v1',
        'moneyWMforRef',        'moneyWMforRef_v1', 
        'moneyAgent',           'moneyAgent_v1', 
        'moneyRef',             'moneyRef_v1', 
        'moneyTTL',             'moneyTTL_v1'
    ); 
    
    /**
    * Получение массива данных из основного кеша
    * 
    * @param T3Report_Header $header
    * @param bool $onlyTotal
    */
    static public function getMainCacheData(T3Report_Header $header, $onlyTotal = false, $period = 'days'){
        //$period = 'hours';
        
        // Изменнение списка получаемых данных в зависимости от типа запроса
        if($period == 'hours'){
            $dateIndexName = 'datetime';
        }
        else {
            $dateIndexName = 'date';  
        }
        
        /*
            Возможные типы статистики:
            
            -  1. статистика без выбора product, channel type, channel id
            -  1.1 Глобальная
            1  1.1.1 Глобальная Общая
            2  1.1.2 Глобальная для Определенного агента   
            3  1.1.3 Глобальная у кого нет агента  
            4  1.2 Для определенного вебмастера 
             
            -  2. статистика когда выбранно что то из product, channel type, channel id 
            -  2.1 Глобальная
            5  2.1.1 Глобальная Общая
            6  2.1.2 Глобальная для Определенного агента
            7  2.1.3 Глобальная у кого нет агента  
            8  2.2 Для определенного вебмастера 
            
            
            MySQL таблицы, для основной выборки данных статистики: 
            cache_summary_days
            cache_summary_days_all
            cache_summary_days_details
            cache_summary_days_details_all
            
        */
        
        $mysql_table = ""; # таблица индексов, по которой будет проходить отбор
        $mysql_where = array();
        $mysql_summary_select = false; # суммировать ли поля в Select запросе
        $show_clicks = false; # показывать ли клики
        $show_ref = false; # показывать ли заработок по реф программе
        
        
        /** @var Zend_Db_Select */
        $select = T3Db::apiReplicant()->select();
        

        // фильтрация по дате
        $select->where("`{$dateIndexName}` BETWEEN '{$header->dateFrom}' AND '{$header->dateTill} 23:59:59'");    

        
        // выбираем объект для выборок
        if(count($header->webmasters)){
            if($header->webmasterAction == 'include') $select->where("`userid` in (?)", $header->webmasters); // для определенного вебмастера
            else                                      $select->where("`userid` not in (?)", $header->webmasters); // для определенного вебмастера
            
            // МЕДЛЕННОЕ МЕСТО
            if($header->agentID){
                $select->where("`userid` in (?)", T3Db::apiReplicant()->fetchCol("select id from users_company_webmaster where agentID=?", $header->agentID));    
            } 
        }
        else {
            if($header->agentWithout)   $select->where("`for`=?", "1");               // Для всех вебмастеров Без Агента
            else if($header->agentID)   $select->where("`for`=?", $header->agentID);  // Для всех вебмастеров определенного Агента
            else                        $select->where("`for`=?", "0");               // Для всех вебмастеров
        }
        
        $productBuyerAgentFull = false;
        try {
            if(T3Users::getCUser()->isRoleBuyerAgent() && is_null($header->product)){
                $productBuyerAgentFull = true;
            }
        }
        catch(Exception $e){}
        
        // Выбор таблиц
        if(is_null($header->product) && is_null($header->channelType) && is_null($header->channelID) && $productBuyerAgentFull == false){
            $mysql_summary_select = false;
            
            if($period == 'hours'){
                if(count($header->webmasters))  $mysql_table = "cache_summary_hours";        // Определенный Вебмастер без параметров
                else                            $mysql_table = "cache_summary_hours_all";    // Глобальные выборки без параметров     
            }
            else {
                if(count($header->webmasters))  $mysql_table = "cache_summary_days";        // Определенный Вебмастер без параметров
                else                            $mysql_table = "cache_summary_days_all";    // Глобальные выборки без параметров 
            }
            
            if($onlyTotal)  $select->from($mysql_table, self::sumColumsArray(self::$columsMainReport, $dateIndexName));
            else            $select->from($mysql_table, self::sumColumsArray(self::$columsMainReport, $dateIndexName));      
        }
        else {
            $mysql_summary_select = true;
            
            if($period == 'hours'){
                if(count($header->webmasters))  $mysql_table = "cache_summary_hours_details";        // Определенный Вебмастер с параметрами   
                else                            $mysql_table = "cache_summary_hours_details_all";    // Глобальные выборки с параметрами  
            }
            else {
                if(count($header->webmasters))  $mysql_table = "cache_summary_days_details";        // Определенный Вебмастер с параметрами   
                else                            $mysql_table = "cache_summary_days_details_all";    // Глобальные выборки с параметрами     
            }
            
            // Дополнительные параметры
            if(!is_null($header->product) || $productBuyerAgentFull){
                if($productBuyerAgentFull){
                    if(count(T3UserBuyerAgents::getProducts())) $select->where("`product` in (?)", T3UserBuyerAgents::getProducts());
                    else                                        $select->where("`product` = 'unknown'", T3UserBuyerAgents::getProducts());
                } 
                else                        $select->where("`product`=?", $header->product);
            }

            if(!is_null($header->channelType)){
                // Тип канала
                switch($header->channelType){
                    case 'js_form': // Все с форм
                        $select->where("(`channel_type`='mobile_form' or `channel_type`='js_form')"); break;

                    case 'form_js': // Только формы для PC
                        $select->where("`channel_type`='js_form'"); break;


                    case 'form_mobile': // Только формы для Mobile
                        $select->where("`channel_type`='mobile_form'"); break;

                    default:
                        $select->where("`channel_type`=?", $header->channelType); break;
                }                                           
            }
            if(!is_null($header->channelID))    $select->where("`channel_id`=?", $header->channelID);       // ID канала
            
            $select->from($mysql_table, self::sumColumsArray(self::$columsMainReport, $dateIndexName));
            //if(!$onlyTotal)$select->group("date");  
        }
        

        if(!$onlyTotal) $select->group($dateIndexName);   

        
        if($onlyTotal){
            //if($_SERVER['REMOTE_ADDR'] == '77.40.61.6') echo (string)$select; die;
            
            // только суммы
            $data = T3Db::apiReplicant()->fetchRow($select);
            unset($data['date']);
            self::addConvertFields($data); 
            
            return $data;     
        }
        else {
            // echo $select->__toString(); die;
            // детальныйе данные
            $data = T3Db::apiReplicant()->fetchAll($select);
            
            $index = array();
            foreach($data as $el){
                if($period == 'hours'){
                    $index[date("Y-m-d H", strtotime($el[$dateIndexName])) . ":00:00"] = $el;    
                }
                else {
                    $index[$el[$dateIndexName]] = $el;     
                }  
            }
            
            if($period == 'hours'){
                $start_deltaDays = round(($header->dateFrom_ts - T3Report_Header::getTodayTS())/3600) - date('H');
                $end_deltaDays   = round(($header->dateTill_ts - T3Report_Header::getTodayTS())/3600);
            }
            else {
                $start_deltaDays = round(($header->dateFrom_ts - T3Report_Header::getTodayTS())/86400);
                $end_deltaDays   = round(($header->dateTill_ts - T3Report_Header::getTodayTS())/86400);   
            }  
            
            $total = array();
            $result = array();
            
            $currentYear = date('Y');
            
            for($i = $end_deltaDays; $i >= $start_deltaDays; $i--){
                if($period == 'hours'){
                    $mktime     =   mktime(date('H')+$i, 0, 0, date('m'), date("d"), date("Y"));
                    $dateYmd    =   date("Y-m-d H:i:s", $mktime);       
                    
                    if($currentYear == substr($dateYmd, 0, 4))  $dateStr    = date('d M H:i', $mktime);
                    else                                        $dateStr    = date('d M, Y H:i', $mktime);   
                    
                    $add = array(
                        'dateStr'           => $dateStr, 
                        'dateStrSelWeekend' => $dateStr,  
                        'dateYmd'           => $dateYmd,
                        'flortTime'         => ((strtotime($dateYmd . " UTC"))*1000), 
                        'weekday'           => date('w', $mktime), 
                    );    
                }
                else {
                    $mktime     =   mktime(0, 0, 0, date('m'), date("d")+$i, date("Y"));
                    $dateYmd    =   date("Y-m-d", $mktime);
                    $weekday    =   date('w', $mktime);       
                    
                    if($currentYear == substr($dateYmd, 0, 4))  $dateStr    = date('d M', $mktime);
                    else                                        $dateStr    = date('d M, Y', $mktime);   
                    
                    $add = array(
                        'dateStr'           => $dateStr, 
                        'dateStrSelWeekend' => $dateStr . (in_array($weekday, array(0,6)) ? ' *' : ''),  
                        'dateYmd'           => $dateYmd,
                        'flortTime'         => ((strtotime($dateYmd . " UTC"))*1000), 
                        'weekday'           => $weekday, 
                    );
                }   
                 
                foreach(self::$columsMainReport as $col){
                    $add[$col] = ifset($index[$dateYmd][$col], 0)+0;
                    $total[$col] = round(ifset($total[$col], 0) + $add[$col], 5);    
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
    * 1. Подсчет переменных конверта
    * 2. Убирает поля которые недоступны из за прав доступа
    * 3. Подсчет Дохода для админа и Вебмастера
    * 
    * @param float $el
    */
    static protected function addConvertFields(&$el){
        $el['ratioStr'] = '-';
        $el['epcStr']   = '-';
        $el['eplStr']   = '-';
        $el['alpStr']   = '-'; 
        
        $el['ratio'] = 0;
        $el['epc']   = 0; 
        $el['epl']   = 0; 
        $el['alp']   = 0; 
        
        if($el['all_leads'] > 0 && $el['sold_leads'] > 0 && $el['moneyWM'] > 0){
            $el['ratio'] = round($el['sold_leads']/$el['all_leads'], 4);
            $el['epl']   = round($el['moneyWM']/$el['all_leads'], 2);
            $el['alp']   = round($el['moneyWM']/$el['sold_leads'], 2);
            
            //$el['ratioStr'] = round($el['all_leads']/$el['sold_leads']) . ":1";
            $el['ratioStr'] = round($el['ratio']*100, 1);
            
            $el['eplStr']   = $el['epl'];
            $el['alpStr']   = $el['alp'];
            
            if($el['uniqueClicks']){
                $el['epc'] = $el['epcStr'] = round($el['moneyWM']/$el['uniqueClicks'], 2);        
            }
        }
        
        $user = T3Users::getInstance()->getCurrentUser();
        
        // Удаление полей котоыре надо показывать пользователям в зависимоти от их прав
        if(!$user->isRoleAdmin()){
            if(!($user->isRoleBuyerAgent() || $user->isRoleWebmasterAgent())){
                // unset($el['sora_leads']);
                unset($el['sora_tnx']);
                unset($el['sora_dup']);
            }

            if(!$user->isRoleWebmasterAgent()){
                unset($el['money_sora_tnx_wm']);
                unset($el['money_sora_dup_wm']);
            }

            // это поле доступно всем
            // unset($el['money_sora_ttl_wm']); - всем кроме админов по соре надо знать только это


            if(!$user->isRoleBuyerAgent()){
                unset($el['money_sora_tnx']);
                unset($el['money_sora_dup']);
                unset($el['money_sora_ttl']);
            }
        }

        if(!($user->isRoleAdmin() || $user->isRoleBuyerAgent())){
            unset($el['moneyTTL']); 
            unset($el['moneyTTL_v1']);       
        }
        
        if(!($user->isRoleAdmin() || $user->isRoleWebmaster())){
            unset($el['moneyRef']);
            unset($el['moneyRef_v1']);      
        }
        
        if(!($user->isRoleAdmin() || $user->isRoleWebmasterAgent())){
            unset($el['moneyAgent']);
            unset($el['moneyAgent_v1']);    
        }

        if($user->isRoleAdmin() || $user->isRoleBuyerAgent()){
            if($el['sora_leads'] > 0){
                $el['sora_epl'] = round($el['money_sora_ttl'] / $el['sora_leads'], 2);
            }
            else {
                $el['sora_epl'] = 0;
            }

            if($el['sora_tnx'] > 0){
                $el['sora_tnx_epl'] = round($el['money_sora_tnx'] / $el['sora_tnx'], 2);
            }
            else {
                $el['sora_tnx_epl'] = 0;
            }

            if($el['sora_dup'] > 0){
                $el['sora_dup_epl'] = round($el['money_sora_dup'] / $el['sora_dup'], 2);
            }
            else {
                $el['sora_dup_epl'] = 0;
            }
        }
        
        // Подсчет доходов
        if($user->isRoleAdmin()){ 
            $el['earnings'] = round($el['moneyTTL'] - $el['moneyWMReturns'] - $el['moneyWM'] - $el['moneyAgent'] - $el['moneyWMforRef'] - $el['moneyBonuses'] - $el['moneyAD'] + $el['money_sora_ttl'] - $el['money_sora_ttl_wm'], 2);
            $el['earnings_v1'] = round($el['moneyTTL_v1'] - $el['moneyWMReturns_v1'] - $el['moneyWM_v1'] - $el['moneyAgent_v1'] - $el['moneyWMforRef_v1'] - $el['moneyBonuses_v1'], 2);    
            
            $el['wm_per'] = "-";
            $el['t3_per'] = "-";
            if($el['moneyTTL'] != 0){
                $el['wm_per'] = round((($el['moneyWM']+$el['moneyAD'])*100) / ($el['moneyWM']+$el['earnings']+$el['moneyAgent']+$el['moneyWMforRef']+$el['moneyAD']), 1);  
                $el['t3_per'] = round(($el['earnings']*100) / ($el['moneyWM']+$el['earnings']+$el['moneyAgent']+$el['moneyWMforRef']+$el['moneyAD']), 1);   
            }

            // sora
            $el['money_sora_dup_t3']    = round($el['money_sora_dup'] - $el['money_sora_dup_wm'], 2);
            $el['money_sora_tnx_t3']    = round($el['money_sora_tnx'] - $el['money_sora_tnx_wm'], 2);
            $el['money_sora_ttl_t3']    = round($el['money_sora_ttl'] - $el['money_sora_ttl_wm'], 2);
        }
        else if($user->isRoleWebmaster()){
            $el['earnings'] = round($el['moneyWMReturns'] + $el['moneyWM'] + $el['moneyBonuses'] + $el['moneyRef'] + $el['moneyAD'] + $el['money_sora_ttl_wm'], 2);
            $el['earnings_v1'] = round($el['moneyWMReturns_v1'] + $el['moneyWM_v1'] + $el['moneyBonuses_v1'] + $el['moneyRef_v1'], 2);    
        }
    }
    
    
    static public function reindexWebmasterDetailsCache($date, $webmaster){
        $lead = new T3Lead();
        T3Db::api()->delete("cache_summary_days_details", "`date`='{$date}' and `userid`='{$webmaster}'"); 
        
        // clicks V2
        $clicksV2 = 0;
        
        // clicks V1          
        $clicksV1 = 0;
        
        // leads V2
        // select 
        $leadsV2 = T3Db::api()->fetchAll("select product,get_method,wm,agn,ref,ttl,channel_id,agentID from leads_data where id BETWEEN (select leads_data.id from leads_data where `datetime` >= '$date' limit 1) AND  (select max(leads_data.id) from leads_data where `datetime` <= '$date 23:59:59' and `datetime` >= '$date') and affid=$webmaster");
        if(count($leadsV2)){
            foreach($leadsV2 as $el){
                $lead->affid        = $webmaster;
                $lead->datetime     = $date;
                $lead->product      = $el['product'];
                $lead->get_method   = $el['get_method'];
                $lead->channel_id   = $el['channel_id'];
                $lead->agentID      = $el['agentID'];
                
                $soldLead = 0; 
                if($el['wm'] > 0) $soldLead = 1;
                
                self::cacheForWebmasterMainRun_OnlyUserDetails(
                    $lead,
                    array(
                        array('all_leads',          1),
                        array('sold_leads',         $soldLead),
                        array('moneyWM',            round((float)$el['wm'],  2)),
                        array('moneyAgent',         round((float)$el['agn'], 2)),
                        array('moneyTTL',           round((float)$el['ttl'], 2)),
                        array('moneyWMforRef',      round((float)$el['ref'], 2)),
                    )
                ); 
            }    
        }
        
        // leads V1
        // 
        /*$webmasterV1 = T3Synh_V1User::getV1ID($webmaster);
        if($webmasterV1){
            $leadsV1 = T3Db::v1()->fetchAll("select `type`,money,totalmoney,agentMoney,channel_type,refmoney,loginAgent from stat where TO_DAYS(`leaddatetime`) = TO_DAYS(?) and affid=?", array($date,$webmasterV1));
            if(count($leadsV1)){
                foreach($leadsV1 as $el){
                    $lead->affid        = $webmaster;
                    $lead->datetime     = $date;
                    $lead->product      = T3Products::oldToNew($el['type']);
                    $lead->get_method   = T3Synh_Functions::getPostMethod($el['channel_type']);
                    $lead->channel_id   = "1";
                    $lead->agentID      = T3UserWebmasterAgents::getNewAgentID($el['loginAgent']);
                    
                    $soldLead = 0; 
                    if($el['money'] > 0) $soldLead = 1;
                    
                    self::cacheForWebmasterMainRun_OnlyUserDetails(
                        $lead,
                        array(
                            array('all_leads',          1),
                            array('sold_leads',         $soldLead),
                            array('moneyWM',            round((float)$el['money'],  2)),
                            array('moneyAgent',         round((float)$el['agentMoney'], 2)),
                            array('moneyTTL',           round((float)$el['totalmoney'], 2)),
                            array('moneyWMforRef',      round((float)$el['refmoney'], 2)),
                            
                            array('all_leads_v1',       1),
                            array('sold_leads_v1',      $soldLead),
                            array('moneyWM_v1',         round((float)$el['money'],  2)),
                            array('moneyAgent_v1',      round((float)$el['agentMoney'], 2)),
                            array('moneyTTL_v1',        round((float)$el['totalmoney'], 2)),
                            array('moneyWMforRef_v1',   round((float)$el['refmoney'], 2)),
                        )
                    ); 
                }    
            }
        }*/
    } 
}
