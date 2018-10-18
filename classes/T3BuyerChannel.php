<?php


// Добавление описания таблицы
/*TableDescription::addTable('buyers_channels', array(
  'id',
  'title',
  'buyer_id',
  'status',
  'email',
  'timezone',
  'minConstPrice'
));*/

TableDescription::addTable('buyers_channels', array(
  'id',
  'product',
  'title',
  'buyer_id',
  array( 'status',        array( 'BuyerChannelStatus' ) ), 
  'filter_datetime' ,
  'email' ,
  array( 'timezone',      array( 'Timezone' ) ),
  array( 'minConstPrice', array( 'Price' ) ),
  'duplicateDays', 
  'globalDuplicate',
  'duplicatePostDays',
  'duplicateGlobalDays',
  'duplicateMethod',
  'testMode',
  'testOptions',
  'ratioSendVsSold',
  'auto_on_off',
  'auto_paused_percent',
  'auto_paused_minutes',
  'priceGame',
  'isDeleted',
  'settings',
  'minDuplicateNums',
  'minDuplicateData',
));



class T3BuyerChannel extends DbSerializable {

    public $id;
    public $title; 
    public $product; 
    public $buyer_id;
    public $status;
    public $filter_datetime = 0;
    public $email;
    public $tech_emails;
    public $timezone;
    public $minConstPrice = '0.00';

    public $filter;
    public $priceRule;
    
    public $duplicateDays = 30; 
    public $globalDuplicate;
    public $duplicatePostDays = -1;
    public $duplicateGlobalDays = -1;
    public $testMode = 0;
    public $testOptions = '';
    
    public $auto_on_off = 0;
    public $auto_paused_percent = 50;
    public $auto_paused_minutes = 10;
    
    public $priceGame = 1;
    
    public $duplicateMethod = 1;
    
    public $isDeleted = 0;

    public $minDuplicateData;
    public $minDuplicateNums;
    
    /**
    * Серелизованная строка, с которой работает класс T3BuyerChannel_Config
    * 
    * @var mixed
    */
    public $settings = '';
    
    /**
    * Количесво отправленных лидов за которое должен быть хотябы один проданный, иначе будут рассылатся уведомления о ошибке
    * @var int
    */
    public $ratioSendVsSold = 20;
    
    /**
    * Объект конфига для текущего канала
    * @var T3BuyerChannel_Config
    */
    protected $config;
    
    static protected $statusOptionsArray = array(
        'just_created'  => array('backgroundColor' => '#FBFCFF', 'title' => 'New'),
        'paused'        => array('backgroundColor' => '#FEDCC0', 'title' => 'Pause'),
        'active'        => array('backgroundColor' => '#D5FAD1', 'title' => 'Active'),
    );

    public function isGlobalDuplicateNow($date = null, $dupDays = null){
        if(is_null($date))      $date       = $this->globalDuplicate;
        if(is_null($dupDays))   $dupDays    = $this->duplicateGlobalDays;
        
        if($dupDays >= 0){
            if(strlen($date) == 10 && substr($date, 0, 4) . substr($date, 5, 2) . substr($date, 8, 2) >= date('Ymd')){
                return true;    
            }
        }
        return false;        
    }

    public function isMinGlobalDuplicateNow($date = null, $dupDays = null){
        if(is_null($date))      $date       = $this->minDuplicateData;
        if(is_null($dupDays))   $dupDays    = $this->minDuplicateNums;

        if($dupDays > 0){
            return true;
        }
        return false;
    }
    
    public function isActive(){
        return ($this->status == 'active' && !$this->isDeleted) ? true : false;    
    }
    
    static public function statusOptions($statusName){
        if(isset(self::$statusOptionsArray[$statusName])){
            return self::$statusOptionsArray[$statusName];
        }
        else {
            return array(
                'title' => $statusName,
                'color' => '#ffffff',
            );
        }          
    }
    
    public function getCountSoldLeads($datatime_PST = null){
        return T3BuyerChannel_SoldTimeZoneCache::getSoldLeads($this, $datatime_PST);        
    }
    
    /**
    * Получение объекта конфига для текущего канала (при первом обращени происходит загрузка)
    * 
    * @return T3BuyerChannel_Config 
    */
    public function getConfig(){
        if(is_null($this->config)){
            $this->config = new T3BuyerChannel_Config();
            $this->config->load($this->id, true);
        }
        return $this->config;    
    }


    public function  __construct() {
        if (!isset($this->className))
            $this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('buyers_channels');
    }
    
    static public function sendSoldInDay ($id){

    }

    /**
    * Проверяет получал ли этот баер, похожий лид
    * 
    * @param T3Lead $lead
    * @return bool
    */
    public function isDuplicate(T3Lead $lead){
        // Количество дней за которые происходит проверка
        return T3BuyerChannels::checkDuplicate($lead, $this); 
        
        // НУЖНО ДОБАВИТЬ ПРОВЕРКУ КАНАЛОВ   
    }
    
    /**
    * Форматирование времени в секундах
    * 
    * @param mixed $start
    */
    protected function secondsFormat($start = null, $corection = 0){
        if(is_null($start))$start = $this->start_temp;
        
        return sprintf("%.05f", microtime(1) - $start + $corection);    
    }
    
    protected $start_temp;
    
    static $cacheGlobalRejectGroups;
    static $cacheOneCompanyGroups;
    
    
    /**
    * Отправка лида на этот канал
    * 
    * @param T3Lead $lead 
    * @param bool $isTest
    * @param float $externalCommissions доля приболи, которую необходимо будет отдать 3 лицам (эта доля не будет учитываться в расперделении прибыли) 
    * 
    * @return T3BuyerChannel_PostResult
    */
    public function post(T3Lead $lead, $isTest = false, $externalCommissions = 0, $priceConflict = true) {
        // загрузка body из базы данных, если он еще не загружен
        $this->start_temp = microtime(1);
        $lead->getBodyFromDatabase();
        
        $PostResult = new T3BuyerChannel_PostResult($lead,$this);
        $PostResult->startDate      = date("Y-m-d H:i:s");
        $PostResult->publisher      = $lead->affid;
        $PostResult->pub_channel    = $lead->channel_id;
        $PostResult->pub_subaccount = $lead->subacc;
        $PostResult->sendLog        = array();
        
        $PostResult->priceRuleWM    = T3PriceRules::getRule($lead, $this)->runProcents(null, true, false, $externalCommissions)->priceWM;
        $PostResult->priceRuleTTL   = T3PriceRules::getRule($lead, $this)->runProcents(null, true, false, $externalCommissions)->priceTTL;
        $PostResult->minPrice       = $lead->getMinPrice();
        
        //$PostResult->save();   
        
        /**
        * Тестирование отправки
        * Для детальной настройки можно принимать обхект $isTest с полным описанием деталей отправки
        */
        $run_checkDuplicate  = true;    // проверять на дупликат 
        $run_globalReject    = true;    // проверять на глобальный реджект определенной группой каналов
        $run_checkFilter     = true;    // проверять фильтры
        $run_payLead         = true;    // оплачивать лид
        
        if((is_bool($isTest) && $isTest) || (is_int($isTest) && $isTest === 1)){
            $PostResult->isTest(true);
            
            $run_checkDuplicate = false;
            $run_globalReject   = false;
            $run_checkFilter    = false;
            $run_payLead        = false;  
        }
        
        if ($lead->product == 'call'){
            $run_checkFilter    = false;    
        }
        
        /**
        *  Загрузка конфиг файла (проверка на ошибки)
        */
        $time_start_temp = microtime(1);
        if (!$this->getConfig()->status) {
            
            $PostResult->setErrorConfig('Config Error',$this->getConfig()->err);
            $PostResult->secondsAll = $this->secondsFormat();
            $PostResult->save();
            $PostResult->addLog();
            
            return $PostResult;
        }
        $PostResult->secondsLoadConfig = $this->secondsFormat($time_start_temp); 
        
        /**
        * Проверка на глобальный Reject
        */
        $time_start_temp = microtime(1);
        if($run_globalReject){
            // кеш групп глобал реджекта
            if(is_null(self::$cacheGlobalRejectGroups)){
                $all = T3Db::api()->fetchAll("select idposting,groupName from buyers_channels_globalreject");
                if(count($all)){
                    foreach($all as $el){
                        if(!isset(self::$cacheGlobalRejectGroups[$el['idposting']])) self::$cacheGlobalRejectGroups[$el['idposting']] = array($el['groupName']);
                        else                                                         self::$cacheGlobalRejectGroups[$el['idposting']][] = $el['groupName']; 
                    }
                }
            }
            $globalRejectGroups = isset(self::$cacheGlobalRejectGroups[$this->id]) ? self::$cacheGlobalRejectGroups[$this->id] : array();
            
            if(count($globalRejectGroups)){
                foreach($globalRejectGroups as $groupGlobalReject){
                    $resultGlobalReject = $lead->getRegisterValue("_GlobalReject_" . $groupGlobalReject); 
                    if(isset($resultGlobalReject)){
                        
                        $PostResult->setGlobalReject($resultGlobalReject);
                        $PostResult->secondsGlobalRejectCheck = microtime(1) - $time_start_temp;
                        $PostResult->secondsAll = $this->secondsFormat();
                        $PostResult->save();
                        $PostResult->addLog();
                        
                        return $PostResult;     
                    }
                }    
            }
            
            // кеш one company групп
            if(is_null(self::$cacheOneCompanyGroups)){
                $all = T3Db::api()->fetchAll("select idposting,groupName from buyers_channels_onecompany");
                if(count($all)){
                    foreach($all as $el){
                        if(!isset(self::$cacheOneCompanyGroups[$el['idposting']])) self::$cacheOneCompanyGroups[$el['idposting']] = array($el['groupName']);
                        else                                                       self::$cacheOneCompanyGroups[$el['idposting']][] = $el['groupName']; 
                    }
                }
            }
            $oneCompanyGroups = isset(self::$cacheOneCompanyGroups[$this->id]) ? self::$cacheOneCompanyGroups[$this->id] : array();  
            
            if(count($oneCompanyGroups)){
                foreach($oneCompanyGroups as $groupOneCompany){
                    $resultOneCompany = $lead->getRegisterValue("_OneCompany_" . $groupOneCompany); 
                    if(isset($resultOneCompany)){
                        
                        $PostResult->setGlobalReject($resultOneCompany);
                        $PostResult->secondsGlobalRejectCheck = microtime(1) - $time_start_temp;
                        $PostResult->secondsAll = $this->secondsFormat();
                        $PostResult->save();
                        $PostResult->addLog();
                        
                        return $PostResult;     
                    }
                }    
            }            
        }
        $PostResult->secondsGlobalRejectCheck = $this->secondsFormat($time_start_temp);   
        
        /**
        * Проверка проходит ли баер фильтры, которые на него настроенны 
        */
        $time_start_temp = microtime(1);
        
        $hardCodeChanges = array();
        
        if($run_checkFilter){
            $filter = T3BuyerFilters::getInstance()->getFilter($this->id, true, true);
            $filterResult = $filter->acceptsLead($lead);      
            if($filterResult->isError()) {   
                $PostResult->setFiltered($filter->getTextReport());
                $PostResult->secondsFilteresCheck = $this->secondsFormat($time_start_temp); 
                $PostResult->secondsAll = $this->secondsFormat();
                $PostResult->save();
                $PostResult->addLog(); 

                return $PostResult;
            }

            /////// Установка значений hard code ////////////

            $hardCodeConditionTypesFields = array(
              "DebtAmount" => 'unsecured_debt_amount',
              "LoanAmount" => 'requested_amount',
              "MonthlyIncome" => 'monthly_income',
              "UKMonthlyIncome" => 'monthly_income',
              "UKBankAccountType" => 'bank_account_type',
                
              'BankAccountMinMonths' => 'bank_account_length_months',
              'EmployerMinMonths' => 'employed_months',
              'LastAddressMinMonths' => 'address_length_months',
                
              'UKAddressLength' => null,  
              'UKJobLength' => null,  
                
            );           

            foreach($filterResult->messages as $message){
            
              if(
                $message->code == T3BuyerFilter_Condition::CHANGE_OFFER 
                && $message->subject == 'UKAddressLength'
              ){

                $address_length_year_old = $lead->getBody()->address_length_year;
                $address_length_months_old = $lead->getBody()->address_length_months;
                $address_length_year_new = (int)($message->data['newValue'] / 12);
                $address_length_months_new = $message->data['newValue'] % 12;
                
                $lead->getBody()->setParams(array(
                  'address_length_year' => $address_length_year_new,
                  'address_length_months' => $address_length_months_new,
                ));   
                
                $hardCodeChanges['address_length_year'] = array(
                  'fieldName' => 'address_length_year',
                  'oldValue' => $address_length_year_old,
                  'newValue' => $address_length_year_new,
                );               
                $hardCodeChanges['address_length_months'] = array(
                  'fieldName' => 'address_length_months',
                  'oldValue' => $address_length_months_old,
                  'newValue' => $address_length_months_new,
                );               
                
                continue;
                
              }else if(
                $message->code == T3BuyerFilter_Condition::CHANGE_OFFER
                && $message->subject == 'UKJobLength'
              ){

                $employed_years_old = $lead->getBody()->employed_years;
                $employed_months_old = $lead->getBody()->employed_months;
                $employed_years_new = (int)($message->data['newValue'] / 12);
                $employed_months_new = $message->data['newValue'] % 12;
                
                $lead->getBody()->setParams(array(
                  'employed_years' => $employed_years_new,
                  'employed_months' => $employed_months_new,
                ));    
                
                $hardCodeChanges['employed_years'] = array(
                  'fieldName' => 'employed_years',
                  'oldValue' => $employed_years_old,
                  'newValue' => $employed_years_new,
                );               
                $hardCodeChanges['employed_months'] = array(
                  'fieldName' => 'employed_months',
                  'oldValue' => $employed_months_old,
                  'newValue' => $employed_months_new,
                );
                
                
                continue;                       
       
              }
              
              if(
                !isset($hardCodeConditionTypesFields[$message->subject]) ||
                $message->code != T3BuyerFilter_Condition::CHANGE_OFFER ||
                empty($message->data['newValue'])
              )
                continue;

              $fieldName = $hardCodeConditionTypesFields[$message->subject];

              $hardCodeChanges[$fieldName] = array(
                'fieldName' => $fieldName,
                'oldValue' => $lead->getBody()->$fieldName,
                'newValue' => $message->data['newValue'],
              );
              
              
              $lead->getBody()->setParams(array(
                $fieldName => $message->data['newValue'],
              ));

            }

            //////////////////////////////////////////////////////// 
        }
        $PostResult->secondsFilteresCheck = $this->secondsFormat($time_start_temp); 
        
        
        /**
        * Проверка на Duplicate. Видел ли этот баер Этот или похожий лид, за определенный период времени
        */
        $time_start_temp = microtime(1);
        if($run_checkDuplicate){
            if($this->isDuplicate($lead)){
                
                $PostResult->setDuplicated();
                $PostResult->secondsDuplicateCheck = $this->secondsFormat($time_start_temp);
                $PostResult->secondsAll = $this->secondsFormat();
                $PostResult->save();
                $PostResult->addLog();

                self::RestoreHardcodedValues($lead, $hardCodeChanges);
                return $PostResult;
            }    
        }
        $PostResult->secondsDuplicateCheck = $this->secondsFormat($time_start_temp);    
        
        
        
        $time_start_temp = microtime(1);
        $CollectClassName   = $this->getConfig()->ConfigModuleClass_Collect; 
        $SendClassName      = $this->getConfig()->ConfigModuleClass_Send; 
        $AnalysisClassName  = $this->getConfig()->ConfigModuleClass_Analysis;
        $PostResult->secondsReadConfig = $this->secondsFormat($time_start_temp);    

        if(T3TestCluster::isTestMode()){
            if(rand(1,15) == 1){
                $paymentResult = $lead->payment(
                    $this, 
                    $this->minConstPrice+10, 
                    array(
                        'isTest'                => !$run_payLead,  
                    )
                ); 
                
                T3Cache_LeadsPayAndRedirects::add_Sold($lead->id, $this->id);
                 
                if($paymentResult){
                    // Оплата за лид прошла успешно
                    if(!$isTest){
                        // сохранение проданного лида в систему инвойсов
                        T3Leads_Sellings::sellLead($lead, $this, $PostResult->getID(), $paymentResult->priceTTL);

                        // добавить проданный лид на этого баера
                        T3BuyerChannel_SoldTimeZoneCache::addSoldLead($this);
                        T3BuyerFilter_MaxPerHourManager::getInstance()->recordLead($this->id);
                    }
                    
                    $RedirectLink = null;
                    if($lead->product == 'payday' || $lead->product == 'ukpayday'){
                        $RedirectLink = 'http://t3leads.com/';
                    }
                    
                    $T3RedurectLink = T3Redirects::createRedirect($lead, $this, $RedirectLink);
                    
                    if ($RedirectLink){
                        T3Cache_LeadsPayAndRedirects::add_createRedirect($lead->id, $this->id); 
                        
                        if(rand(1,20) != 1){
                            file_get_contents($T3RedurectLink);  
                        }
                    }
                    
                    $PostResult->setSold(
                        $paymentResult->priceWM,      // Цена вебмастера 
                        $paymentResult->priceTTL,     // Цена общая 
                        $T3RedurectLink               // Редирект ссылка
                    ); 
                }    
            }
            else {
                $PostResult->setRejected('Test Mode');
            }
        }
        else {
            // Сбор Данных
            
            $time_start_temp = microtime(1);
            $collectObject = new $CollectClassName();
            $collectResult = $collectObject->run($lead, $this, $isTest);
            
            
            // Если во время формирования запроса были запросы к баеру, то вычесть это время, и запомнить его.
            $additionalPostToBuyerRunTime_Collect = 0;
            if($collectResult->additionalPostToBuyerRunTime > 0){
                $additionalPostToBuyerRunTime_Collect = $collectResult->additionalPostToBuyerRunTime;
            }
            
            $PostResult->secondsRunCollect = $this->secondsFormat($time_start_temp, -$additionalPostToBuyerRunTime_Collect); 
            
            if($collectResult->pingLog){
                $PostResult->sendLog = array_merge($PostResult->sendLog, $collectResult->pingLog);
                $PostResult->isSend(true);  
            }    

            if (!$collectResult->ok()){
                if($collectResult->isManualExit()){
                    $PostResult->setRejected("Manual Exit: {$collectResult->error}"); // Пользовательский выход
                    $PostResult->secondsAll = $this->secondsFormat();
                    $PostResult->save();
                    $PostResult->addLog();

                    self::RestoreHardcodedValues($lead, $hardCodeChanges);
                    return $PostResult;
                }
                else if($collectResult->isPingReject()){
                    $PostResult->setRejected("Manual Exit: {$collectResult->error}"); // Пользовательский выход
                    $PostResult->secondsAll = $this->secondsFormat();
                    $PostResult->save();
                    $PostResult->addLog();
                    self::RestoreHardcodedValues($lead, $hardCodeChanges);
                    
                    ////// Запись в татистику байеров ///////
                    T3BuyersStats::getInstance()->recordOrEnqueueLead($lead, $PostResult, $this->buyer_id, $this->id);
               
                    ////////////////////////////////////////
                    
                    return $PostResult;
                }
                else {
                    $PostResult->setError($collectResult); // скорее всего ошибка в конфиге и там нет каких то переменных  
                }  
            }
            else {
                // Отправка Собранных Данных
                
                $time_start_temp = microtime(1);
                $sendObject = new $SendClassName();
                
                // закрыть конект к базе данных на время отправки данных
                T3Db::api()->closeConnection();
                
                $sendResult = $sendObject->run($lead, $this, $collectResult, $isTest);
                
                // снова открыть соединение   
                T3Db::api()->query("SET NAMES UTF8");          
                T3Db::api()->query("SET SESSION time_zone = 'US/Pacific'");   
                
                $PostResult->secondsRunSend = $this->secondsFormat($time_start_temp, $additionalPostToBuyerRunTime_Collect);   
                $PostResult->sendLog = array_merge($PostResult->sendLog, $sendResult->log);
                
                // записать в кеш ssid что лид отправился
                T3Report_SSIDSummary::load($lead, $this)->post();
                
                if ($sendResult->ok()) {    
                    $PostResult->isSend(true);
                    $sendObject->addOneCompanyReject(); // добавление в реестр записей, если канал входит в группы oneCompany

                    // Анализ полученного ответа
                    $time_start_temp = microtime(1);
                    $analysisObject = new $AnalysisClassName();
                    $analysisResult = $analysisObject->run($lead, $this, $sendResult, $isTest);
                    
                    if(!$analysisResult->notDuplicate){
                        // Добавление записи в таблицу дупликатных постов
                        if($run_checkDuplicate) T3BuyerChannels::addDuplicatePostItem($lead, $this);
                        T3BuyerChannel_PostDatetimes::add($this->id);    
                    }
                    
                    // Если во время парсинга ответа были запросы к баеру, то вычесть это время, и прибавить его к времени отправки
                    if($analysisResult->additionalPostToBuyerRunTime > 0){
                        $PostResult->secondsRunSend = sprintf("%.05f", $PostResult->secondsRunSend + $analysisResult->additionalPostToBuyerRunTime);
                    }  
                    
                    $PostResult->secondsRunAnalysis = $this->secondsFormat($time_start_temp, -$analysisResult->additionalPostToBuyerRunTime); 
                    
                    if(isset($analysisResult->pingLog) && is_array($analysisResult->pingLog) && count($analysisResult->pingLog)){
                        $PostResult->sendLog = array_merge($PostResult->sendLog, $analysisResult->pingLog); 
                    }  
                    
                    if($analysisResult->isSold()){
                        $this->ratioPostVsSold_addSold();
                        $this->setLastSoldNow();
                        
                        // Добавление записи в таблицу дупликатных солдов
                        if($run_checkDuplicate) T3BuyerChannels::addDuplicateItem($lead, $this);
                        
                        // Анализ ответа ответил что лид продан
                        $time_start_temp = microtime(1);
                        $paymentResult = $lead->payment(
                            $this, 
                            $analysisResult->totalPrice, 
                            array(
                                'isTest'                => !$run_payLead,
                                'externalCommissions'   => $externalCommissions,
                                'additionalIncome'      => $analysisResult->additionalIncome,
                                'priceConflict'         => $priceConflict
                            )
                        ); 
                        
                        //$this->sendSoldInDay($this->id);
                        T3Cache_LeadsPayAndRedirects::add_Sold($lead->id,$this->id);
                        
                         
                        if($paymentResult){
                            // Оплата за лид прошла успешно
                            
                            // добавить проданный лид на этого баера
                            T3BuyerChannel_SoldTimeZoneCache::addSoldLead($this);
                            T3BuyerFilter_MaxPerHourManager::getInstance()->recordLead($this->id);
                            
                            // сохранение проданного лида в систему инвойсов
                            if(!$isTest){
                                $selObj = T3Leads_Sellings::sellLead($lead, $this, $PostResult->getID(), $paymentResult->priceTTL);
                            }
                            
                            $T3RedurectLink = T3Redirects::createRedirect($lead, $this, $analysisResult->getRedirectLink());
                            
                            if ($analysisResult->getRedirectLink()){
                                T3Cache_LeadsPayAndRedirects::add_createRedirect($lead->id,$this->id);    
                            }
                            
                            // записать в кеш ssid что лид продался
                            T3Report_SSIDSummary::load($lead, $this)->sold($paymentResult->priceTTL);
                            
                            $PostResult->setSold(
                                $paymentResult->priceWM,      // Цена вебмастера 
                                $paymentResult->priceTTL,     // Цена общая 
                                $T3RedurectLink               // Редирект ссылка
                            ); 
                            $PostResult->priceExternal = $paymentResult->priceExternal;
                        } 
                        else {
                            // Что делать, как ответить об этом buyer?
                            // Не стоит беспокоиться об этом, функция payment оповостит buyer'а об этом сама.
                            $PostResult->setPriceConflict($analysisResult->message);
                        }
                        $PostResult->secondsPayment = $this->secondsFormat($time_start_temp); 
                    } 
                    else {
                        $this->ratioPostVsSold_addNotSold();
                        
                        /*
                        - Лид не продан:
                          - Ответ известный, понятен, нормальный, и т.д.
                          - Ответ известен, но ошибки в канале. Например у баера стоит фильтр - штат калифорния, а в ответе об ошибке не подошел штат.
                          - Ответ не понятен - Например MySql error
                         */
                        if($analysisResult->isConfigError())          $PostResult->setError($analysisResult);                
                        else if($analysisResult->isParserError())     $PostResult->setErrorAnalysis($analysisResult);                
                        else if($analysisResult->isLogicError())      $PostResult->setErrorAnalysis($analysisResult);                
                        else if($analysisResult->isReject())          $PostResult->setRejected($analysisResult->message);                
                        else                                          $PostResult->setErrorAnalysis($analysisResult);
                    }
                     
                    /*
                     Для пайдай таких баеров пока что нету
                        - Согласие с предложением
                        - Отказ от предложения:
                          - Ответ известный, понятен, нормальный, и т.д.
                          - Ответ известен, но ошибки в канале. Например у баера стоит фильтр - штат калифорния, а в ответе об ошибке не подошел штат.
                          - Ответ не понятен - Например MySql error
                    */
                } 
                else {
                    /**
                    * Можно удалить все ifs, ну я оставил их тут что бы было понятно как можно отловить какоето событие,
                    * потом это отлавливание надо будет вынести в оддельный медот, что бы можно было отлавиливать ошибки и уведомлять покупателей 
                    */
                    
                    if($sendResult->isConfigError())        $PostResult->setError($sendResult);                
                    else if($sendResult->isNoConnect())     $PostResult->setErrorSend($sendResult);                
                    else if($sendResult->is500Error())      $PostResult->setErrorSend($sendResult);
                    else if($sendResult->isNoResolved())    $PostResult->setErrorSend($sendResult);                
                    else if($sendResult->isTimeout()){
                        $PostResult->setErrorSend($sendResult);
                        $PostResult->isTimeout(true);
                        $sendObject->addOneCompanyReject(); // добавление в реестр записей, если канал входит в группы oneCompany
                    }                
                    else                                    $PostResult->setErrorSend($sendResult);
                }
            }
        }
        
        // Если надо вернуть хардкотовые значения назад, возвращаем их.
        // Запихни какие значения менялись в $PostResult и сохраняй это в базу, они в дальнейшем понадобятся 
        // (Когда надо буцдет экспорт лидов для баера делать, что бы не было такого что мы посылали одно а при жкспорте другое)

        

        // Возвращаем значения, которые были изменены на hard code

        self::RestoreHardcodedValues($lead, $hardCodeChanges);

        /*if(is_array($hardCodeChanges) && count($hardCodeChanges)){
            foreach($hardCodeChanges as $change){
              $lead->getBody()->setParams(array(
                $change['fieldName'] => $change['oldValue'],
              ));          
            }
        }*/
        
        ///////////////////////////////////////////////////////////
        
           
        
        ////// Запись в татистику байеров ///////
        // посылка лида на продажу прошла успешненько          
        T3BuyersStats::getInstance()->recordOrEnqueueLead($lead, $PostResult, $this->buyer_id, $this->id);

        ////////////////////////////////////////


        $PostResult->secondsAll = $this->secondsFormat();
        $PostResult->save();
        $PostResult->addLog();

        

        return $PostResult;
    }

    public static function RestoreHardcodedValues(T3Lead $lead, &$hardCodeChanges){
      if(is_array($hardCodeChanges) && count($hardCodeChanges)){
        foreach($hardCodeChanges as $change){
          $lead->getBody()->setParams(array(
            $change['fieldName'] => $change['oldValue'],
          ));
        }
      }
    }
    
    
    public function ratioPostVsSold_addSold(){
        ////////////////// Для уведомлений /////////////////////////////
        T3Db::api()->delete("buyer_channel_ratio_post_vs_sold_notification", "posting_id='{$this->id}' and `status`='open'");  
        
        ////////////////// Для истории /////////////////////////////
        $all = T3Db::api()->fetchRow("select `create_date`,(`count`+1) as `count` from buyer_channel_ratio_post_vs_sold_run where posting_id=?", $this->id);
        T3Db::api()->delete("buyer_channel_ratio_post_vs_sold_run", "posting_id='{$this->id}'"); 
        
        if($all === false){
            $all = array(
                'create_date'   => date("Y-m-d H:i:s"),
                'count'         => 1,
            );    
        } 
        
        T3Db::api()->insert("buyer_channel_ratio_post_vs_sold_log", array(
            'buyer_id'      =>  $this->buyer_id,
            'posting_id'    =>  $this->id, 
            'period_from'   =>  $all['create_date'], 
            'period_till'   =>  date("Y-m-d H:i:s"), 
            'count'         =>  $all['count'],
        ));
    }
    
    public function ratioPostVsSold_addNotSold(){
        ////////////////// Для уведомлений /////////////////////////////
        $count = T3Db::api()->fetchRow("select id, `count`, create_date from buyer_channel_ratio_post_vs_sold_notification where posting_id=? and `status`='open' and `create_date` > ? limit 1", array(
            $this->id,
            date("Y-m-d H:i:s", mktime(date("H")-6, date("i"), date("s"), date("m"), date("d"), date("Y"))),
        )); 
        
        if($count === false){
            T3Db::api()->insert("buyer_channel_ratio_post_vs_sold_notification", array(
                'create_date'   => new Zend_Db_Expr("NOW()"),
                'buyer_id'      => $this->buyer_id,
                'posting_id'    => $this->id,  
                'count'         => '1',  
                'status'        => 'open',  
            )); 
        }
        else {
            if($count['count']+1 >= $this->ratioSendVsSold){
                // превышен лимит
                T3Db::api()->update("buyer_channel_ratio_post_vs_sold_notification", array(
                    'count'     => new Zend_Db_Expr("`count`+1"),
                    'end_date'  => new Zend_Db_Expr("NOW()"),   
                    'status'    => 'close',
                ), "id='{$count['id']}'");
                
                // отправка админам по системе фатальных ошибок
                T3FatalMessage::sendMessageAsync("RatioPostVsSold", array(
                    'channel'       => T3Cache_BuyerChannel::get($this->id, true),
                    'channelText'   => T3Cache_Buyer::render($this->buyer_id, false) . ":" . T3Cache_Posting::render($this->id, false),
                    'date1'         => $count['create_date'],
                    'date2'         => date("Y-m-d H:i:s"),
                    'count'         => ($count['count']+1), 
                    'buyerID'       => $this->buyer_id,
                ));
                
                // отправка баер агентам          
                $postVsSoldNotificationEmails = T3UserBuyerAgents::getAgentsEmails($this->product, 'postVsSold');
                
                if(is_array($postVsSoldNotificationEmails) && count($postVsSoldNotificationEmails)){
                    T3Mail::createMessage('postVsSoldNotification', array (
                        'channel'       => T3Cache_BuyerChannel::get($this->id, true),   
                        'date1'         => $count['create_date'],
                        'date2'         => date("Y-m-d H:i:s"),
                        'count'         => ($count['count']+1), 
                        'buyerID'       => $this->buyer_id,
                        'PostingID'     => $this->id,
                    )) 
                    ->setSubject("Buyers Conversion Notification: " . T3Cache_Buyer::render($this->buyer_id, false) . ":" . T3Cache_Posting::render($this->id, false))                                                                         
                    ->addToArray($postVsSoldNotificationEmails)                   
                    ->SendMail(); 
                }
            }
            else {
                // ждемс...
                T3Db::api()->update("buyer_channel_ratio_post_vs_sold_notification", array(
                    'count' => new Zend_Db_Expr("`count`+1"),
                ), "id='{$count['id']}'");
            }        
        } 
        
        ////////////////// Для истории /////////////////////////////
        try {
            T3Db::api()->insert("buyer_channel_ratio_post_vs_sold_run", array(
                'create_date'   => new Zend_Db_Expr("NOW()"),
                'buyer_id'      => $this->buyer_id,
                'posting_id'    => $this->id,
                'count'         => 1
            ));     
        }
        catch(Exception $e){
            T3Db::api()->update("buyer_channel_ratio_post_vs_sold_run", array(
                'count' => new Zend_Db_Expr("`count`+1"),
            ), "posting_id='" . (int)$this->id . "'"); 
        } 
    }

    /**
    * @return T3BuyerFilter
    */
    public function getFilter($lazy = true) {
        if ($lazy && !is_null($this->filter))
            return $this->filter;
        $this->filter = T3BuyerFilter::createFromDatabase($this->id);
        $this->filter->channel = $this;
        return $this->filter;
    }

    public function getLeadsBoughtThisDay() {

    }

    public function setId($newId) {
        parent::setId($newId);
        if (!is_null($this->filter)) {
            $this->filter->channelId = $this->id;
            foreach($this->filter->conditions as $v)
            $v->channel_id = $this->id;
        }
    }

    public function getPostingWeight() {
        return 1;
    }

    public static function getLeadsAcceptedByChannel($channelId) {
        throw new Exception('Not Implemented');
    }

    public static function createFromDatabase($conditions) {
        return self::createFromDatabaseByClass($conditions, __CLASS__);
    }

    public static function createFromArray(&$array) {
        return self::createFromArrayByClass($array, __CLASS__);
    }
    
    /**
    * Получение массива документация на основе которых построен данный постинг
    * отдельно для Collect Send и Analysis файлов
    * 
    * @return array ARRAY('collect' => ARRAY, 'send' =>  ARRAY, 'analysis' => ARRAY),
    */
    public function getFilesDocumentations(){
        $return = array(
            'collect'   =>  array(),
            'send'      =>  array(),
            'analysis'  =>  array(),
        );
        
        if($this->getConfig()->status){
            $filesClasses = array(
                'collect'   =>  $this->getConfig()->ConfigModuleClass_Collect, 
                'send'      =>  $this->getConfig()->ConfigModuleClass_Send, 
                'analysis'  =>  $this->getConfig()->ConfigModuleClass_Analysis,
            );
            
            foreach($filesClasses as $name => $className){
                /**
                * @var T3PostingFile_Abstract
                */
                $obj = new $className();
                $return[$name] = $obj->getDocumentations();  
            }
        }
        
        return $return; 
    }
    
    
     
    /**
    * Создает новый постинг и возвращает его объект
    * 
    * @param string $title
    * @param string $product
    * @param int $buyerID
    * @param string $collectFile
    * @param string $sendFile
    * @param string $analysisFile
    * @param float $minConstPrice
    * @param string(3) $timeZone    
    * 
    * @return T3BuyerChannel
    */
    static public function createNewChannel($title, $product, $buyerID, $collectFile, $sendFile, $analysisFile, $minConstPrice, $timeZone = 'pst'){
        $posting = new T3BuyerChannel();
        
        $posting->title = $title;
        $posting->product = $product;
        $posting->buyer_id = $buyerID;
        $posting->timezone = $timeZone;
        $posting->status = 'just_created';
        $posting->minConstPrice = $minConstPrice;
        
        $posting->insertIntoDatabase();
        
        $config = new T3BuyerChannel_Config;
        $config->create(
            $posting->id,
            $collectFile,
            $sendFile,
            $analysisFile
        );    
        
        return $posting;    
    }
    
    /**
    * Создание нового постинга на основе другого постинга
    * 
    * @param mixed $copyChannelID
    * @param mixed $title
    * @param mixed $minConstPrice
    * @param mixed $copyConfigurations
    * @param mixed $copyFilters
    * @param mixed $copyPriceRules
    * 
    * @return T3BuyerChannel
    */
    static public function copyChannel($copyChannelID, $title, $buyerID, $minConstPrice = null, $copyConfigurations = true, $copyFilters = true, $copyPriceRules = true){
        $copyPosting = new T3BuyerChannel();
        $copyPosting->fromDatabase((int)$copyChannelID);
        
        if($copyPosting->id){
            if(is_null($minConstPrice))     $minConstPrice = $copyPosting->minConstPrice;    
            else                            $minConstPrice = round((float)$minConstPrice, 2);       
            
            /** 
            * @var T3BuyerChannel
            */
            $posting = self::createNewChannel(
                $title, 
                $copyPosting->product, 
                (int)$buyerID, 
                $copyPosting->getConfig()->PostingConfigModules['Collect'], 
                $copyPosting->getConfig()->PostingConfigModules['Send'], 
                $copyPosting->getConfig()->PostingConfigModules['Analysis'], 
                $minConstPrice, 
                $copyPosting->timezone
            );
            
            // копирование конфигураций
            if($copyConfigurations){
                $posting->getConfig()->PostingConfig = $copyPosting->getConfig()->PostingConfig;
                $posting->getConfig()->save();   
            }
            
            // копирование фильтров
            if($copyFilters){
                T3BuyerFilters::copyFilters($copyPosting->id, $posting->id);           
            }
            
            // копирование правил на цены
            if($copyPriceRules){
                /*
                * Копирование правил цен. Если цена канала с которого копируются цены отличается от цены канала на который происходит копирование, 
                * то константные значения перобразовываются так что пропорции сохраняются. 
                */
                // !!!!!!!!!! добавить    
            }
            
            return $posting;
        }
        else {
            return false;
        }    
    }
    
    public function getPostResults($condition = array()){
        return array();
        
        /*
        if(is_string($condition))$condition = array($condition);
        
        $where = "";
        if(is_array($condition) && count($condition)){
            foreach($condition as $where_str){
                $where.=$where_str." and "; 
            }   
        }
        $where.="buyerChannelID=".$this->id;
        
        $tables = array('buyers1_channels_post_results');
        $query = "";
        $n = count($tables);
        for ($i=0;$i<$n;$i++){
            $query.=sprintf("(SELECT * FROM %s WHERE %s)",$tables[$i]['table_name'],$where);
            if ($i<($n-1)){
                $query.=" UNION ";    
            }    
        }

        $result = $this->database->fetchAll($query);
        
        foreach($result as $id => &$res){
            if(isset($res['sendLog']) && is_string($res['sendLog'])){
                $result[$id]['sendLog'] = varImport($res['sendLog']);
            }    
        }
        
        return $result; 
        */   
    }
    
    /**
    * Установка групп глобального реджекта
    * 
    * @param array $array
    */
    private function setMainGroups($array, $configValueName, $table){
        if(is_array($array)){
            // сохранение данных в конфиг
            $this->getConfig()->setValues($configValueName, $array);
            $this->getConfig()->save();

            // удаление всех записей для этого канала из базы данных
            $this->database->delete($table, 'idposting=' . $this->id);
            
            
            // запись даные в базу данных
            if(count($array)){
                foreach($array as $name){
                    $insert = array(
                        'idposting'     =>  $this->id,
                        'groupName'     =>  $name,
                    );
                    
                    $this->database->insert($table, $insert);        
                }           
            }
        } 
    }
    
    /**
    * Установка групп глобального реджекта
    * 
    * @param array $array
    */
    public function setGlobalRejectGroups($array){
        $this->setMainGroups($array, 'GlobalReject', 'buyers_channels_globalreject'); 
    }
    
    /**
    * Установка групп oneCompany
    * 
    * @param array $array
    */
    public function setOneCompanyGroups($array){
        $this->setMainGroups($array, 'OneCompany', 'buyers_channels_onecompany'); 
    } 
    
    /**
    * Список каналов куда постался лид
    * 
    * @param int $id
    * @return mass
    */
    static public function getChannelsList($id){
        $result = array();
        
        $lead = new T3Lead();
        $lead->fromDatabase($id);
        
        if($lead->id){   
            $result = T3BuyerChannel_Logs::getAllHeads($lead, array('buyerChannelID', 'status'));
            
            if(count($result)){
                T3BuyerChannel_Logs::loadAllStatuses();
                foreach($result as $k => $v){
                    $result[$k]['status'] = T3BuyerChannel_Logs::getStatusName($v['status']);        
                }
            }      
        }
        
        return $result;
    }
    
    /**
    * Получение лога по id
    * 
    * @param int $id
    * @return mass
    */
    static public function getLogByID($id){
        /*
        $self = new self();
        
        $tables = array('buyers1_channels_post_results');
        $where="";
        $where.="id=".$id;
        $query = "";
        $n = count($tables);
        for ($i=0;$i<$n;$i++){
            $query.=sprintf("(SELECT * FROM %s WHERE %s)",$tables[$i]['table_name'],$where);
            if ($i<($n-1)){
                $query.=" UNION ";    
            }    
        }
        
        return $self->database->fetchAll($query);
        */
    }
    
    
    public function getTestModeOptions(){
        $result = array();
        
        $lines = explode("\n", $this->testOptions);
        foreach($lines as $line){
            $line = trim($line);
            
            if(strlen($line)){
                $a = explode("=", $line, 2);
                
                if(count($a) == 2){
                    $result[trim($a['0'])] = trim($a['1']);
                }    
            }    
        }
        
        return $result;
    }
    
    
    public function setStatusActive($params = array()){
        $this->status = 'active';
        $this->saveToDatabase(); 

        $data = array(
            'posting_id'	=> (int)$this->id,
        	'date_create'	=> date("Y-m-d H:i:s"),
        	'status'	=> 1,
        	'on_off_by_buyer'	=> 2,
        	'ip'	=> $_SERVER['REMOTE_ADDR'],
        	'user_id'	=> T3Users::getCUser()->id,
        );
        T3Db::api()->insert('posting_on_off_log', $data);
        $data['posting_status_id'] = T3Db::api()->lastInsertId();
 

        $event_type_id = T3TimeLine_EventType::getIdByType(T3TimeLine_EventType::POSTING_STATUS_CHANGED);
        if($event_type_id){
            T3TimeLine_Event::add($this->buyer_id,
                                  T3TimeLine_Event::TYPE_BUYER,
                                  $event_type_id,
                                  array('posting_id' => $this->id ,'status' => $this->status, 'posting_status_id' => $data['posting_status_id'] ,'table_with_data' => 'posting_on_off_log'),
                                  T3TimeLine_Event::RATING_GOOD
            );              
        }
           

    }
    
    public function setStatusPaused($params = array()){
        $this->status = 'paused';
        $this->saveToDatabase(); 

        $data = array(
            'posting_id'	=> (int)$this->id,
        	'date_create'	=> date("Y-m-d H:i:s"),
        	'status'	=> 2,
        	'on_off_by_buyer'	=> 2,
        	'ip'	=> $_SERVER['REMOTE_ADDR'],
        	'user_id'	=> T3Users::getCUser()->id,
        );

        T3Db::api()->insert('posting_on_off_log', $data);
        $data['posting_status_id'] = T3Db::api()->lastInsertId();

        $event_type_id = T3TimeLine_EventType::getIdByType(T3TimeLine_EventType::POSTING_STATUS_CHANGED);
        if($event_type_id){
            T3TimeLine_Event::add($this->buyer_id,
                                  T3TimeLine_Event::TYPE_BUYER,
                                  $event_type_id,
                                  array('posting_id' => $this->id ,'status' => $this->status, 'posting_status_id' => $data['posting_status_id'], 'table_with_data' => 'posting_on_off_log'),
                                  T3TimeLine_Event::RATING_BAD
            );              
        }
    
       
    }
    
    public function fromDatabase($conditions, $fillObjects = array(), $liteLoadCols = array()) {
        if(is_numeric($conditions) && !count($fillObjects) && !count($liteLoadCols)){
            // пул загрузки каналов баеров
            $array = T3BuyerChannels::getPoolChannelsData($conditions);    
        }
        else {
            $array = self::fromDatabaseStatic($this->tables, $conditions, $this->database, $liteLoadCols);
        }

        if ($array === false)
          return false;

        $this->fromArray($array);
        foreach($fillObjects as $k => $v)
          $this->$v->fromArray($array);

        $this->existsInDatabase = true;

        return true;
    
    }

    /**
     * Установить для данного канала текущее время в last_sold
     *
     * @return $this
     */
    public function setLastSoldNow(){
        if($this->id){
            T3Db::api()->update('buyers_channels', array(
                'last_sold' => date('Y-m-d H:i:s')
            ), "id=" . (int)$this->id);
        }

        return $this;
    }

    protected $cache_soldCardInfo;

    public function getSoldCardInfo(){
        if(is_null($this->cache_soldCardInfo)){
            $this->cache_soldCardInfo = T3Db::apiReplicant()->fetchRow(
                "SELECT `logo`, `title`, `description` FROM `buyers_channels_sold_card` WHERE `id`=?",
                $this->id
            );

            if($this->cache_soldCardInfo === false){
                $this->cache_soldCardInfo = array(
                      'logo'        => '',
                      'title'       => '',
                      'description' => '',
                );
            }
        }

        return $this->cache_soldCardInfo;
    }

    public function setSoldCardInfo($data){
        if($this->id){
            $update = array(
                'logo'        => trim(ifset($data['logo'])),
                'title'       => trim(ifset($data['title'])),
                'description' => trim(ifset($data['description'])),
            );

            $this->cache_soldCardInfo = $update;

            try {
                T3Db::api()->insert("buyers_channels_sold_card", array('id' => $this->id) + $update);
            }
            catch(Exception $e){
                T3Db::api()->update(
                    "buyers_channels_sold_card",
                    $update,
                    "`id`=" . (int) $this->id
                );
            }
        }
    }

    /**
     * Рендер карты или null, если не указанны ни название ни лого
     *
     * @return string
     */
    public function renderSoldCard(){
        $all = $this->getSoldCardInfo();

        if(strlen($all['title'] . $all['logo'])){
            MyZend_Site::addCSS('sold_card.css');

            return "<div class='sold_card'>
                <div>" .
                    (strlen($all['logo']) ?
                        "<div class='sold_card_logo_div'>
                            <img src='" . htmlspecialchars($all['logo']) . "'>
                        </div>" : ""
                    ).
                    "<div class='sold_card_text_div'>" .
                        (strlen($all['title']) ?
                            "<div class='sold_card_title'>{$all['title']}</div>" : ""
                        )
                        .
                        (strlen($all['description']) ?
                            "<div class='sold_card_description'>{$all['description']}</div>" : ""
                        ) .
                    "</div>
                </div>
            </div>";
        }

        return null;
    }

}
