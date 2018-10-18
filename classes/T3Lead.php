<?php

TableDescription::addTable('leads_data', array(
    'id',
    'num',
    'getId',
    'product',
    'status',
    'get_method',
    'is_test',
    'is_mobile',
    'affid',
    'refaffid',
    'firstWM',
    'wm',
    'ref',
    'agn',
    'ttl',
    'datetime',
    'ip_address',
    'subacc',
    'subacc_str',
    'keywords',
    'keywords_str',
    'max_price', 
    'min_prices', 
    'data_email',
    'data_phone',
    'data_ssn',
    'data_state',
    'data_city',
    'channel_id',
    'comment',
    'agentID',
    
    'agentWMProcent',
    'agentADMProcent',
    'refWMProcent',
    'refADMProcent',
));

class T3Lead extends DbSerializable{
    public $id;
    public $num;
    public $getId;
    public $product;
    public $status;
    public $get_method;
    public $is_mobile = 0;

    public $is_test = 0;
    public $affid;
    public $refaffid;
    public $firstWM = 0;
    public $wm = 0;
    public $ref = 0;
    public $agn = 0;
    public $ttl = 0;
    public $datetime;
    public $ip_address;
    public $subacc = 0;
    public $subacc_str = '';
    public $keywords = 0;
    public $keywords_str;
    public $max_price;
    public $min_prices = '';
    public $data_email;
    public $data_phone;
    public $data_ssn;
    public $data_state;
    public $data_city;
    public $channel_id;
    public $comment;
    public $agentID;
    public $agentWMProcent = 2;
    public $agentADMProcent = 1;
    public $refWMProcent = 5;
    public $refADMProcent = 0;
    
    public $user_source;
    public $user_keyword;
    public $user_referref;
    public $user_parsed_source;
    public $user_parsed_keyword;
    public $user_clickid;

    /**
    * @var T3LeadBody_Abstract
    */
    public $body;
    public $channel;

    private $webmasterCompany_leadID;
    private $webmasterCompany;

    public $isDuplicate;    

    public $similarLeadsIds;
    
    /**
    * асоциативный массив, переменных привязанных к лиду, которые сохраняются в базе данных и не умирают после завершения сессии 
    * 
    * @var array mixed
    */
    protected $registerValues;
    
    /**
    * Массив постингов на которые лид уже отправлялся
    * 
    * @var array(posting1, posting2, ...)
    */
    protected $postingsSened = array(); 
    
    /**
    * Автотически сохранять постинги при каждой отправке
    * 
    * @var bool
    */
    protected $postingsSenedAutoSave = true;
    
    /**
    * Минимальная цена за которую можно продавать этот лид в этой сессии (значение не сохраняется в базу данных)
    * @var mixed
    */
    public $minPrice = 0;
    
    public function setMinPrice($price){
        $price = (float)$price;
        if($price < 0)$price = 0;
        if($price > 9999)$price = 9999;
        $price = round($price, 2); 
        
        $this->minPrice = $price;  
        
        if($this->min_prices != '')$this->min_prices.= ", ";
        $this->min_prices.= $this->minPrice;
    }
    
    public function fillAgentAndCalculatePercents($calculetePercents = true){
        $this->agentID  =   T3WebmasterCompanys::getAgentID($this->affid);
        
        if($calculetePercents){
            if($this->agentID){
                $this->agentWMProcent   =   T3UserWebmasterAgents::getAgent($this->agentID)->procentWM * 100;   
                $this->agentADMProcent  =   T3UserWebmasterAgents::getAgent($this->agentID)->procentADM * 100;
                if ($this->agentID == 1054150 && $this->affid == 40966){
                    $this->agentWMProcent = 1;
                }
            } 
            else {
                $this->agentWMProcent   =   '0';   
                $this->agentADMProcent  =   '0';     
            }
        }
    }
    
    public function fillReferalAndCalculatePercents($calculetePercents = true){
        $this->refaffid     =   T3WebmasterCompanys::getReferalID($this->affid); 
        
        if($calculetePercents){
            if($this->refaffid){
                // Если у пользователя есть Реферал, то его процент будет = 3% от цены лида полученной вебмастером.
                $this->refWMProcent = '3';             
                $this->refADMProcent = '0';
                if ($this->refaffid == 40619 && $this->affid == 40966){
                    $this->refWMProcent = 2;
                }
                else if($this->refaffid == 49772){
                    $this->refWMProcent = 5;
                }
            }
            else {
                $this->refWMProcent = '0';             
                $this->refADMProcent = '0'; 
            }
        }
    }
    

    /**
    * Получить secure Sub ID, для этого лида, и для определенного канала
    * 
    * @param T3BuyerChannel $buyerChannel
    * @return int
    */
    public function getSecureSubID(T3BuyerChannel $buyerChannel){
        $SecureSubID = new T3SecureSubID();
        return  $SecureSubID->get_ssID($this, $buyerChannel);
    }
    
    /**
    * Созданеи нового объекта тестового лида
    * @return T3Lead
    */
    static public function createTestLead($product) {
        $testLead = new T3Lead($product);
        
        $testLead->id = '999999';
        $testLead->affid = '3000';
        $testLead->ip_address = myHttp::get_ip_num('76.9.31.146');
        $testLead->data_email = "test@t3leads.com";
        
        $testLead->body->testValues(); 
        
        return $testLead;   
    }
    
    
    /**
    * Оплата текущего лида
    *
    * @param T3BuyerChannel $buyerChannel
    * @param mixed $totalPrice  необязательный параметр, показывающий какую цену возратил покупатель, при отправке ему лида (Используется тольок есть цена Динамическая)
    * @param array $options Настроки выплаты 
    * 
    * @return T3PriceRule Объект последней оплаты
    */
    public function payment(T3BuyerChannel $buyerChannel, $totalPrice = null, $options = array()){
        // анализ настроек
        $isTest         =   false;  // При тестовой оплате начисления не добавляются на баланс и не сохраняются в базу данных
        $isAutomatic    =   true;   // При автоматичесом начислении имя администратора, который проихвел оплату = .script
        $externalCommissions = 0;   // доля внешних коммисий
        $additionalIncome    = 0;   // допорлнительный доход t3
        
        if(isset($options['isTest']) && $options['isTest'] == true)             $isTest = true;
        if(isset($options['isAutomatic']) && $options['isAutomatic'] == false)  $isAutomatic = false;
        if(isset($options['externalCommissions']) && $options['externalCommissions'] >= 0)  $externalCommissions = (float)$options['externalCommissions'];
        if(isset($options['additionalIncome']))  $additionalIncome = round((float)$options['additionalIncome'], 2);
        
        
        // цена лида для Вебмасетра до проведения оплаты  
        $wm_temp = $this->wm;

        if((
            !isset($options['priceConflict']) || $options['priceConflict'] == true) &&
            !is_null($totalPrice) &&
            $totalPrice < $buyerChannel->minConstPrice
        ){
            // ошибка, лид не оплачивается и считается не проданным, потому что цена которую отдал баер меньше чем заявленная минималка
            // ДОБАВИТЬ ЛОГИРОВАНИЕ ОШИБКИ!
            return null;
        }
        else if(
            (isset($options['priceConflict']) && $options['priceConflict'] == true) &&
            !is_null($totalPrice) &&
            $totalPrice < 0
        ){
            return null;
        }
        else {
            // Если оплата тестовая, то начисления не идут
            if($isTest){
                $T3PriceRule = T3PriceRules::getRule($this,$buyerChannel)->runProcents($totalPrice);  
                return $T3PriceRule;
            }
            
            // varExport(T3PriceRules::getRule($this,$buyerChannel)->getCache());
            
            /** @var T3PriceRule */
            $T3PriceRule = T3PriceRules::getRule($this,$buyerChannel)->runProcents($totalPrice, true, true, $externalCommissions);
            
            // добавочный доход
            $T3PriceRule->priceT3Leads += $additionalIncome;
            $T3PriceRule->priceTTL += $additionalIncome;
            
            
            // Выставление цен для CJ
            if($this->get_method == 'js_form' && T3Cj::isWebmaster($this->affid)){
                $T3PriceRule->priceWM = T3Cj::getCJPrice($this, $T3PriceRule->priceTTL);       
            }
            
            // начисление денег на этот лид
            $this->wm  += $T3PriceRule->priceWM;
            $this->agn += $T3PriceRule->priceAgent;
            $this->ref += $T3PriceRule->priceReferal;
            $this->ttl += $T3PriceRule->priceTTL;

            T3Visitors::updateEarnings($this->id, $this->wm, $this->agn, $this->ref, $this->ttl);

            // анализ, перехода лида с проданного в не проданный и из не проданного в проданный
            if($wm_temp <= 0 && $this->wm > 0)      $soldLead = 1;
            else if($wm_temp > 0 && $this->wm <= 0) $soldLead = -1;
            else                                    $soldLead = 0;
            
            // изменение стсатуса лида
            if(
                $soldLead == 1 || 
                (
                    $this->wm>0 && 
                    $this->status!='sold'
                ) 
            ){
                $this->status = 'sold';    
            }
            else if($soldLead == -1){
                $this->status = 'return'; // лид перешел из состяния проданный в состояние не проданный    
            }
            
            $this->saveToDatabase();
              
            // кеширование денег вебмастеру на текущий баланс
            if($T3PriceRule->priceWM) $this->getWebmasterCompany()->updateBalance($T3PriceRule->priceWM);
            
            if($this->agentID && $T3PriceRule->priceAgent != 0){
                // кеширование денег агенту на текущий баланс
                T3UserWebmasterAgents::updateBalance($this->agentID, $T3PriceRule->priceAgent, $this->affid, $this->id, $this->product, 'v2');   
            } 
            
            if($this->refaffid && $T3PriceRule->priceReferal != 0){
                // кеширование денег рефу на текущий баланс
                T3WebmasterCompanys::getCompany($this->refaffid)->updateBalance($T3PriceRule->priceReferal);
                
                T3Report_Summary::addReferalPay(
                    $this,
                    $T3PriceRule
                ); 
                
                // запись в систему пейментов
                T3Db::api()->insert("webmasters_referral_addings", array(
                    'webmaster_id' => $this->refaffid,
                    'payment_id' => null,
                    'action_sum' => $T3PriceRule->priceReferal,
                    'action_datetime' => mySqlDateTimeFormat(),
                    'referred_webmaster_id' => $this->affid,
                    'referred_lead_id' => $this->id,
                ));   
            }
            
            // запись в систему пейментов
            T3Db::api()->insert("webmasters_leads_sellings", array(
                'lead_id'               => $this->id, 
                'channel_id'            => $this->channel_id, 
                'subaccount_id'         => $this->subacc, 
                'webmaster_id'          => $this->affid, 
                'payment_id'            => null, 
                'getting_log_record_id' => '0', 
                'action_datetime'       => new Zend_Db_Expr("NOW()"), 
                'action_sum'            => $T3PriceRule->priceWM, 
                'lead_email'            => $this->data_email, 
                'lead_ssn'              => null,
                'lead_home_phone'       => $this->data_phone, 
                'lead_product'          => $this->product, 
            ));
            
            
            // запись в систему пейментов Buer Agents
            if( $buyer_agent_id = T3UserBuyerAgent::getBuyerAgent($buyerChannel->buyer_id, $this->product) ) {
            	if( $T3PriceRule->priceTTL > 0 ) {
	                T3Db::api()->insert("buyer_agents_leads_sellings", array(
	            		    'date_create'      		 => new Zend_Db_Expr("NOW()"),
	            		    'posting_id'			=> $buyerChannel->id,
	            		    'buyer_id'				=> $buyerChannel->buyer_id,
	            		    'lead_id'               => $this->id,
	            		    'buyer_agent_id'		=> $buyer_agent_id,
	            		    'product'          		=> $this->product,
	            		    'price_ttl'				=> $T3PriceRule->priceTTL,
	            		    'payment_id'            => null,
	                ));
            	}
            }
            
            
            
            // запись в билинг
            if($isAutomatic){
                $adminName = '.script';
                $adminUserID = '0';
            }
            else {
                $adminName = T3Users::getInstance()->getCurrentUser()->login;
                $adminUserID = T3Users::getInstance()->getCurrentUser()->id;
            }
            
            $this->database->insert('leads_billing',array(
                'idLead'            =>  $this->id,
                'product'           =>  $this->product,
                
                'buyerID'           =>  $buyerChannel->buyer_id,
                'buyerChannelID'    =>  $buyerChannel->id,
                
                'webmasterID'       =>  $this->affid,
                'agentID'           =>  $this->agentID,
                'referID'           =>  $this->refaffid,
                
                'userMoney'         =>  $T3PriceRule->priceWM,
                'agentMoney'        =>  $T3PriceRule->priceAgent,
                'referMoney'        =>  $T3PriceRule->priceReferal,
                'totalMoney'        =>  $T3PriceRule->priceTTL,
                
                'adminName'         =>  $adminName, 
                'adminUserID'       =>  $adminUserID,
            ));
            
            
            // Если трафик пришел с вебмастра AD (t3ppc), записать его данные в систему по ads
            if($this->affid == T3Aliases::getID('ad')){
                $r = explode("-", $this->subacc_str);
                if(count($r) == 2){
                    $clickID = T3Ad::decodeSecureID("{$r[0]}-{$r[1]}");
                    
                    $clickArray = T3Db::api()->fetchRow("select webmaster,`datetime` from ad_clicks where id=?", $clickID);
                    
                    /** @var T3Lead */
                    $leadForPayment = clone $this;
                    $leadForPayment->id = 1;
                    $leadForPayment->datetime = $clickArray['datetime'];
                    $leadForPayment->affid = $clickArray['webmaster'];
                    $leadForPayment->channel_id = 1;
                    $leadForPayment->get_method = 'ads'; 
                    $leadForPayment->fillReferalAndCalculatePercents();
                    $leadForPayment->fillAgentAndCalculatePercents();
                    
                    $T3PriceRuleAd = T3PriceRules::getRule($leadForPayment, $buyerChannel)->runProcents($totalPrice, false, false, $externalCommissions);
                    
                    if($clickArray){
                        // Деньги для пейментов
                        T3Db::api()->insert("ad_money", array(
                            'webmaster' => $clickArray['webmaster'],
                            'click'     => $clickID,
                            'lead'      => $this->id,
                            'wm'        => $T3PriceRuleAd->priceWM,
                            'agn'       => $T3PriceRuleAd->priceAgent,
                            'ref'       => $T3PriceRuleAd->priceReferal,
                            't3'        => $T3PriceRuleAd->priceT3Leads,
                            'ttl'       => $T3PriceRuleAd->priceTTL,
                        ));
                        
                        // Кеши репортов
                        T3Report_Summary::addAdsPay(
                            date('Y-m-d H:i:s'),
                            $leadForPayment->affid, 
                            $leadForPayment->product,
                            $this->channel_id,
                            $T3PriceRuleAd   
                        );
                        
                        
                        // Изменнеие клика, добавление в него денег
                        T3Db::api()->update("ad_clicks", array(
                            'wm'  => new Zend_Db_Expr("round(wm +{$T3PriceRuleAd->priceWM}, 2)"),
                            'ref' => new Zend_Db_Expr("round(ref+{$T3PriceRuleAd->priceReferal}, 2)"),
                            'agn' => new Zend_Db_Expr("round(agn+{$T3PriceRuleAd->priceAgent}, 2)"),
                            't3'  => new Zend_Db_Expr("round(t3 +{$T3PriceRuleAd->priceT3Leads}, 2)"),
                            'ttl' => new Zend_Db_Expr("round(ttl+{$T3PriceRuleAd->priceTTL}, 2)"),
                        ), "id={$clickID}");
                        
                        
                        // кеширование денег вебмастеру на текущий баланс
                        if($T3PriceRuleAd->priceWM) T3WebmasterCompanys::getCompany($leadForPayment->affid)->updateBalance($T3PriceRuleAd->priceWM);
                        
                        if($leadForPayment->agentID && $T3PriceRuleAd->priceAgent != 0){
                            // кеширование денег агенту на текущий баланс, а также в его билинговую систему
                            T3UserWebmasterAgents::updateBalance(
                                $leadForPayment->agentID, 
                                $T3PriceRuleAd->priceAgent, 
                                $leadForPayment->affid, 
                                $leadForPayment->id, 
                                $leadForPayment->product, 
                                'v2'
                            );   
                        } 
                        
                        
                        if($leadForPayment->refaffid && $T3PriceRuleAd->priceReferal != 0){
                            // кеширование денег рефу на текущий баланс
                            T3WebmasterCompanys::getCompany($leadForPayment->refaffid)->updateBalance($T3PriceRuleAd->priceReferal);
                            
                            T3Report_Summary::addReferalPay(
                                $leadForPayment,
                                $T3PriceRuleAd
                            ); 
                            
                            // запись в систему пейментов
                            T3Db::api()->insert("webmasters_referral_addings", array(
                                'webmaster_id'              =>  $leadForPayment->refaffid,
                                'payment_id'                =>  null,
                                'action_sum'                =>  $T3PriceRuleAd->priceReferal,
                                'action_datetime'           =>  mySqlDateTimeFormat(),
                                'referred_webmaster_id'     =>  $leadForPayment->affid,
                                'referred_lead_id'          =>  $leadForPayment->id,
                            ));   
                        }
                        
                        
                    }                   
                }
            }
            else {
                // кеширование данных
                T3Report_Summary::addPayLead(
                    $soldLead,
                    $this,
                    $buyerChannel,
                    $T3PriceRule
                );    
            }
            
            
            return $T3PriceRule;
        }
        
    }

    public function getWebmasterCompany(){
        // изменено из похожего кода
        if(isset($this->id) && !is_null($this->id) && isset($this->affid) && !is_null($this->affid)){
            return T3WebmasterCompanys::getCompany($this->affid);
        }
        else {
            return NULL;   
        }
    }

    public function __construct($product = null) {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('leads_data');
        if(strlen($product)) $this->setProduct($product);

    }

    public function setProduct($product){
        $this->product = $product;
        $productClassBody = T3Products::getProduct($this->product, 'class_body');   
        //$this->body = is_null($product) ? null : new T3LeadsProducts::$productsClasses[$this->product]($this); 
        $this->body = is_null($product) ? null : new $productClassBody($this);        
    }
    
    /**
    * Получить значение из Рееастра текущего лида
    * Если значения не сущестует, возвращает null
    * 
    * @param string $name
    */
    public function getRegisterValue($name, $default = null){
        if($this->isRegisterValue($name))  
            return $this->registerValues[$name];    
        return $default;
    }
    
    /**
    * Записать значение в реестр текущего лида
    * 
    * @param string $name
    * @param mixed $value
    */
    public function setRegisterValue($name, $value){
        if(
            is_array($value) ||
            is_bool($value) ||
            is_double($value) ||
            is_float($value) || 
            is_int($value) ||
            is_long($value) || 
            is_integer($value) ||
            is_null($value) ||
            is_string($value)
        ){
            try{ 
                $this->database->insert("leads_register",array(
                    'id'  => $this->id,
                    'var' => $name,
                    'val' => var_export($value, true),
                ));
                $this->registerValues[$name] =  $value;
                return true;
            }
            catch(Exception $e){
                try{ 
                    $this->database->update(
                        "leads_register",
                        array('val' => var_export($value, true)),
                        "id={$this->id} and `var` = " . $this->database->quote($name)
                    );
                    $this->registerValues[$name] =  $value;
                    return true;
                }
                catch(Exception $e){}   
            }
        }
        return false;       
    }
    
    /**
    * Проверка значения в реестре лида
    * 
    * @param string $name
    */
    public function isRegisterValue($name){
        if(!isset($this->registerValues[$name])){
            // поиск значения в базе данных
            $result = $this->database->fetchRow(
                'select `val` from `leads_register` where id=? and var=?', 
                array($this->id, $name), 
                Zend_Db::FETCH_OBJ 
            );
              
            if($result){
                $run_string = '$this->registerValues[$name] = ' . $result->val . ';';
                @eval($run_string);  
            }
            else {
                // вот так тупо, потому что изначально так!!!
                $this->registerValues[$name] = '!--NULL--!';   
            }  
        }   
        
        if(isset($this->registerValues[$name]) && $this->registerValues[$name] != '!--NULL--!'){
            return true;
        }
        
        return false;
    }

    public function getChannel($lazy = true){

        if($lazy && !is_null($this->channel))
            return $this->channel;

        // В будущем эту процедуру нужно будет обобщить с помощью класса DbSerializable
        // throw new Exception('Not Implemented');
        
        /*
        $channel = new T3Channel_Abstract();
        $array = DbSerializable::fromDatabaseStatic('channels', $this->channel_id);   
        $channel = $channel->morphIntoActualClass($array);
        $channel->fromDatabase($this->channel_id);
        $this->channel = $channel;

        return $this->channel;
        */
        
        
        
        return T3Channels::getChannel($this->channel_id);

    }

    public function getChannelType($straightFromDatabase = true){

        if($straightFromDatabase){
            $ar = $this->database->fetchAll("
                SELECT `type` as t
                FROM channels
                WHERE id = ?
            ", array($this->channel_id));
            
            if(count($ar)==0)
                return false;
            else
                return $ar[0]['t'];
        }
        else{
            return $this->getChannel()->type;
        }

    }

    public function insertIntoDatabase($printErrors = true){
        if($this->affid > 0){
            $this->num = T3Leads::addGetID($this->affid);
            $this->getId = "{$this->num}.{$this->affid}";    
        }
        
        parent::insertIntoDatabase($printErrors);
        
        $this->getBody();
        $this->body->id = $this->id;
        $this->body->insertIntoDatabase();
    }

    /**
    * put your comment there...
    * 
    * @param mixed $lazy
    * @return T3LeadBody_Abstract
    */
    public function getBody($lazy = true){
        if($lazy && !is_null($this->body))
            return $this->body;
        $this->setProduct($this->product);
        return $this->body;
    }

    public function getBodyFromDatabase($lazy = true){
        if($lazy && !is_null($this->body))
            return $this->body;
        $this->getBody()->fromDatabase($this->id);
        return $this->body;
    }

    public function fromRequest(&$request){
      $this->datetime = mySqlDateTimeFormat();
      $this->setProduct($request['product']);


        
      $this->body->fromRequest($request);
      $this->body->passDoublingFieldsToLead();
    }

    public static function createFromRequest(&$request){
      $object = new T3Lead();
      $object->fromRequest($request);
      return $object;
    }

  public static function validateRequest(&$request, $finalValidation = true){

    $b = isset($request['product']) && T3LeadsProducts::productExists($request['product']);

    if(!$b){
      $report = new Report();
      $report->error('product', Report_Codes::INVALID_VALUE);
      return $report;
    }

    $class = T3LeadsProducts::$productsClasses[$request['product']];
    $bodyValidationReport = callStaticMethod($class, 'validateRequest', $request, $finalValidation);

    return $bodyValidationReport;

  }

    public function isDuplicate($lazy = true){

      if($lazy && !is_null($this->isDuplicate))
        return $this->isDuplicate;

      return count($this->getSimilarLeadsIds($lazy)) != 0;

    }

    public function getSimilarLeadsIds($lazy = true){
      if($lazy && $this->similarLeadsIds !== null)
        return $this->similarLeadsIds;

      /*'leadtype' => 'product',
      'get_method' => 'getMethod',
      'datetime' => 'datetime',
      'data_email' => 'email',
      'data_phone' => 'phone',
      'data_ssn' => 'ssn',
      'data_state' => 'state',*/

      $ar = $this->toArray();

      $this->similarLeadsIds = $this->database->fetchCol('
        SELECT id
        FROM leads_data
        WHERE
          product = ? AND
          get_method = ? AND
          data_ssn = ?
      ', array($ar['product'], $ar['get_method'], T3SSN::getID($ar['data_ssn'])));

      return $this->similarLeadsIds;
    }

    public function sendToCallCenter(){
      throw new Exception("Not Implemented");
    }

    public function haveToSendToCallCenter(){
      throw new Exception("Not Implemented");
    }

    public static function createFromDatabase($conditions){
        return self::createFromDatabaseByClass($conditions, __CLASS__);
    }

    public static function createFromArray(&$array){
        return self::createFromArrayByClass($array, __CLASS__);
    }

    /**
    * @desc Флаг показывающий будет ли проходить проверка по MinPrice, при отправке функцией postToBuyer()
    */
    protected $postToBuyer_CheckMinPrice = true;
    
    /**
    * Отправка лида на определенный канал покупателя, не проверяя minPrice 
    */
    public function postToBuyer_NotCheckMinPrice($buyerChannel, $isTest = false, $isAutoLeadStatusEdit = true){
        $this->postToBuyer_CheckMinPrice = false;
        $r = $this->postToBuyer($buyerChannel, $isTest, $isAutoLeadStatusEdit);
        $this->postToBuyer_CheckMinPrice = true;
        return $r;    
    }
    
    
    
    public function postToCallBuyer($buyerChannel, $isTest = false, $isAutoLeadStatusEdit = true, $externalCommissions = 0){
        if(is_numeric($buyerChannel)){
            $buyerChannel_id = $buyerChannel;
            
            /** @var T3BuyerChannel */                                    
            $buyerChannel = T3BuyerChannels::getChannel($buyerChannel_id);
        }  
        else {
            if(!is_a($buyerChannel,'T3BuyerChannel')){
                // ОШИБКА: переданная переменная не является числом и не является объектом типа T3BuyerChannel
                return null;
            }    
        }
        
        if(!$buyerChannel->id){
            // ОШИБКА: Объект не определен
            return null;     
        }
        
        $result = $buyerChannel->post($this, $isTest, $externalCommissions);
        
        if($isAutoLeadStatusEdit && !$result->isTest()){
            if(!$this->isStatusSold() && $result->isSold()){
                $this->setStatusSold();
                $this->saveToDatabase();    
            } 
            else if(
                !$result->isSold() && $result->isSend() && 
                ($this->isStatusDuplicate() || $this->isStatusProcess() || $this->isStatusNoSend() || $this->isStatusVerification())
            ){
                    $this->setStatusReject();
                    $this->saveToDatabase();        
            }  
        }
        
        if($this->postingsSenedAutoSave){
            $this->savePostingsSended();
        }
        
        return $result;
    }
    
    
    /**
    * Отправка лида на определенный канал покупателя
    * 
    * @param T3BuyerChannel|int $buyerChannel ID канала или Загруженный объект этого канала
    * @param bool $isTest
    * 
    * @return T3BuyerChannel_PostResult 
    */
    public function postToBuyer($buyerChannel, $isTest = false, $isAutoLeadStatusEdit = true, $externalCommissions = 0, $priceConflict = true){
        if(is_numeric($buyerChannel)){
            $buyerChannel_id = $buyerChannel;

            /** @var T3BuyerChannel */
            $buyerChannel = T3BuyerChannels::getChannel($buyerChannel_id);
        }  
        else {
            if(!is_a($buyerChannel,'T3BuyerChannel')){
                // ОШИБКА: переданная переменная не является числом и не является объектом типа T3BuyerChannel
                return null;
            }    
        }
        
        if(!$buyerChannel->id){
            // ОШИБКА: Объект не определен
            return null;
        }
        
        if(!$buyerChannel->isActive() && !$isTest){
            // Постинг на паузе
            return null;
        }
        
        if(!$buyerChannel->filter_datetime && !$isTest){
            // Постинг не принимает лиды сейчас
            return null;
        }

        if($this->postToBuyer_CheckMinPrice){
            if(T3PriceRules::getRule($this, $buyerChannel, $this->minPrice)->runProcents(null, true, false, $externalCommissions)->priceWM < $this->minPrice){
                // Постинг не подходит по мин прайсу
                return null;
            }
        }

        $result = $buyerChannel->post($this, $isTest, $externalCommissions, $priceConflict);

        if($isAutoLeadStatusEdit && !$result->isTest()){
            if(!$this->isStatusSold() && $result->isSold()){
                $this->setStatusSold();
                $this->saveToDatabase();    
            } 
            else if(
                !$result->isSold() && $result->isSend() && 
                ($this->isStatusDuplicate() || $this->isStatusProcess() || $this->isStatusNoSend() || $this->isStatusVerification())
            ){
                    $this->setStatusReject();
                    $this->saveToDatabase();        
            }  
        }
        
        if($this->postingsSenedAutoSave){
            $this->savePostingsSended();
        }
        
        return $result;
    }
    
    /**
    * Получить массив постингов, нга которые уже отправлялся этот лид
    * 
    * array(
    *   posting1,
    *   posting2,
    *   ...
    * )
    * 
    */
    public function getPostingsSended(){
        return $this->postingsSened;
    }
    
    /**
    * Сохранить постинги в базу
    */
    public function savePostingsSended(){
        $this->setRegisterValue("postingsSened", $this->postingsSened);
    }
    
    /**
    * Добавить постинг на который делалась отправка
    * 
    * @param mixed $postingID
    */
    public function addPostingsSended($postingID){
        $this->postingsSened[] = $postingID;    
    }

    static protected $cache_device_types = array();

    /**
     * @return string 'unknown','mobile','tablet','desktop'
     */
    public function getDeviceType(){
        if(!isset(self::$cache_device_types[$this->id])){
            $temp = T3Db::api()->fetchOne("select `type` from leads_device where id=?", array($this->id));
            self::$cache_device_types[$this->id] = $temp === false ? 'unknown' : $temp;
        }

        return self::$cache_device_types[$this->id];
    }
    
    /**
    * Отправка лида на определенный Pingtree 
    * 
    * @param T3PingTree $pingTree|int  ID pingTree или Загруженный! объект этого pingTree
    * 
    * @return T3Lead_PingtreePostResult Результат отправки по PingTree    
    */
    public function postToPingtree($pingTree, $isTest = false, $stopIfSold0 = false, $priceConflict = true){
        $pingtreePostResult = new T3Lead_PingtreePostResult();
        
        // не сохранять данные о законченных постах при каждой отправке
        $this->postingsSenedAutoSave = false;
        
        if(is_numeric($pingTree)){
            $pingTree = new T3PingTree($pingTree); 
        }   

        if(!is_a($pingTree, 'T3PingTree')){
            // ОШИБКА: $pingTree не является объектом типа T3PingTree
            $pingtreePostResult->setSystemError('Invalid Value $pingTree');
            
            if($this->isStatusProcess()) $this->setStatusReject();
        }
        else {
            if(is_array($pingTree->order) && count($pingTree->order)){
                /******************************************************************************************************
                * 
                *    фильтрация первый шаг. когда мы имеет просто массив каналов и отсеиваем его по максимуму без загрузки каналов и филттров
                *    отсеивание каналов в 2 этапа, что бы запрос к базе был меньше для перепостанных запросов
                * 
                ****************************************/
                // удаление не активных каналов         
                $runAll = $this->getPostingsSended();      // каналы на которые этот лид уже постался
                foreach($pingTree->order as $k => $buyerChannelID){
                    if(in_array($buyerChannelID, $runAll)){
                        unset($pingTree->order[$k]);
                    }
                } 
                
                if(count($pingTree->order)){
                    $activeChannels = T3Db::api()->fetchCol(   // все активные на данный момент каналы
                        "select id from buyers_channels where id in (" . implode(",", $pingTree->order) . ") and `status`='active' and filter_datetime=1"
                    ); 
                    foreach($pingTree->order as $k => $buyerChannelID){
                        if(!in_array($buyerChannelID, $activeChannels)){
                            unset($pingTree->order[$k]);
                        }
                    } 
                } 

                T3BuyerChannels::setPotencialPoolIDS($pingTree->order);
                /*****************************************************************************************************/
                
                /* Последовательная отправка на каналы покупателей, которые перечисленны в PingTree */
                $sold       =   false;
                $reject     =   false;
                $filtered   =   false;
                $duplicate  =   false;
                
                // Включить режим что бы логи записывались не сразу
                T3BuyersStats::getInstance()->enqueueLeadsMode = true;//
                T3BuyerChannel_PostDatetimes::setInsertModeRealTime(false);
                T3BuyerChannel_Reasons::setInsertModeRealTime(false);  
                T3BuyerChannel_Logs::setInsertModeRealTime(false);
                T3SecureSubID::setInsertModeRealTime(false);
                       
                $postToMainPingtree     = true;  
                $postToFirstLook        = false; 
                $firstLookCommission    = 0; 
                $resultRedirectLink     = null;
                $resultWebmasterValue   = 0;
                $resultTotalValue       = 0;
                
                /******************************************************************************************************
                * 
                *    First Look System
                * 
                ****************************************/
                if($this->is_repost === false){
                    // если это первое получение лида
                    $postingsFirstLook = T3Db::api()->fetchAll(
                        "select posting, percentage_of_resale, probability_of_inclusion from buyers_channels_first_look where product=? and webmaster=?", 
                        array(
                            T3Products::getID($this->product), 
                            $this->affid
                        )
                    );
                    
                    shuffle($postingsFirstLook);
                    
                       
                    if(count($postingsFirstLook)){
                        // Установить в кешевый класс текущего вебмастра
                        T3Report_FirstLook::setWebmaster($this->affid);
                        
                        foreach($postingsFirstLook as $postingFirstLookArray){
                            $postingFirstLook = $postingFirstLookArray['posting'];
                            
                            // Устанвоить в кешевый класс текущий FL постинг с которым идет работа
                            T3Report_FirstLook::setPosting($postingFirstLook);
                                
                            /*****************
                            * 
                            *     Что бы была возможность не показывать постинг всегда, добавлется рандомный показ
                            * 
                            * 
                                X = [0, 1]     - входящий процент
                                RAND = [0.0000000005, 1]  - случайная величина  =  rand(1, 2147483647) / 2147483647 (есть небольшое смещение веротностей на долю = 0.0000000005, ну оно не значиительно)

                                Если X <  RAND -> false
                                Если X >= RAND -> true   
                            */
                            
                            $probability_of_inclusion_rand = rand(1, 2147483647) / 2147483647;
                            $probability_of_inclusion = $postingFirstLookArray['probability_of_inclusion']; 
                            
                            if($probability_of_inclusion >= $probability_of_inclusion_rand && !in_array($postingFirstLook, $runAll)){
                                $postResult = $this->postToBuyer($postingFirstLook, $isTest, false);  
                                
                                if($postResult){                           
                                    if($postResult->isSold()){
                                        // Добавить инфомацию что был новый FL лид
                                        T3Report_FirstLook::addLeadAll();
                                        
                                        // остановить продажу. т.к. лид был продан баеру 
                                        $sold = true;
                                        
                                        $postToFirstLook        = true;
                                        $firstLookCommission    = $postingFirstLookArray['percentage_of_resale'];
                                                
                                        $resultWebmasterValue   = $postResult->price; 
                                        $resultTotalValue       = $postResult->priceTTL;
                                        
                                        // Если лид продался на FirstLook баера, то он уже подошел на минпрайс и перекрыл его, проверять других не имеетт смысла
                                        $this->postToBuyer_CheckMinPrice = false;
                                        
                                        // Дорбавить в кеши информацию о том что лид продан на FL канал
                                        T3Report_FirstLook::addLeadSold($postResult->price, $postResult->priceTTL);
                                        
                                        if(strlen($postResult->redirectLink)){
                                            $postToMainPingtree     = false;   
                                            $resultRedirectLink     = $postResult->redirectLink; 
                                        }
                                        else {
                                            // TODO: FL + No Interest    
                                            T3Report_FirstLook::addLeadNoInterest();
                                        }
                                        
                                        break; // выйти из цикла фирст лук постингов                      
                                    }
                                    else if($postResult->isFiltered() && $postResult->status != 'GlobalReject'){
                                        // Добавить инфомацию что был новый FL лид
                                        T3Report_FirstLook::addLeadAll();
                                        
                                        // лид не подошел по филтрам
                                        $filtered = true;
                                        
                                        // Записать в кеши что FL лид был отфильтован
                                        T3Report_FirstLook::addLeadFiltered();
                                        
                                    }
                                    else if($postResult->isSend() && !$postResult->isSold()){
                                        // Добавить инфомацию что был новый FL лид
                                        T3Report_FirstLook::addLeadAll();
                                        
                                        // Лид был доставлен, и не продан
                                        $reject = true;
                                        
                                        // Такого Быть не должно в текущем плане!!!!!!
                                    }
                                    else if($postResult->isDuplicated()){
                                        // Баер уже видел этот лид
                                        $duplicate = true;
                                    }    
                                }
                            }    
                        }
                    }                                                          
                }  
                /*****************************************************************************************************/

                $solds_count = 0;
                $solds_count_max   = (int)T3Products::getProduct($this->product, 'solds_count');
                if ($pingTree->id == 115){
                    $solds_count_max = 10;
                }
                if($postToMainPingtree && count($pingTree->order)){
                    foreach($pingTree->order as $buyerChannelID){

                        $postResult = $this->postToBuyer($buyerChannelID, $isTest, false, $firstLookCommission, $priceConflict);

                        if($postResult){
                            $pingtreePostResult->addPostResult($postResult);
                            
                            if($postResult->isSold()){
                                // остановить продажу. т.к. лид был продан баеру 
                                $resultRedirectLink     = $postResult->redirectLink;
                                $resultWebmasterValue   = round($resultWebmasterValue + $postResult->price, 2); 
                                $resultTotalValue       = round($resultTotalValue + $postResult->priceTTL, 2);
                                
                                if($postToFirstLook && $postResult->priceExternal){
                                    // Не интересующий FL постинг лид, продан на другого баера
                                    T3Report_FirstLook::addLeadResold(
                                        $postResult->priceExternal,
                                        $postResult->price,
                                        $postResult->priceTTL
                                    );                                    
                                    
                                    // необходимо заплатить баеру долю $firstLookCommission от суммы, а остальное расперелять по стандартной схеме    
                                    $ttl = -$postResult->priceExternal;                   
                                    
                                    $buyerChannel = T3BuyerChannels::getChannel($postingFirstLook);
                                    
                                    // изменение баланса лида
                                    $this->ttl = round($this->ttl + $ttl, 2); 
                                    $this->saveToDatabase();

                                    // изменение баданса баера
                                    T3Db::api()->update("users_company_buyer", array(
                                        "balance" => new Zend_Db_Expr("balance + {$ttl}")
                                    ), "id='{$buyerChannel->id}'");     
                                         
                                    // визиторс
                                    T3Visitors::updateEarnings($this->id, $this->wm, $this->agn, $this->ref, $this->ttl);
                                    
                                    // запись в систему пейментов Buer Agents
                                    if($buyer_agent_id = T3UserBuyerAgent::getBuyerAgent($buyerChannel->buyer_id, $this->product)){
                                        T3Db::api()->insert("buyer_agents_leads_sellings", array(
                                            'date_create'           => date('Y-m-d H:i:s'),
                                            'posting_id'            => $buyerChannel->id,
                                            'buyer_id'              => $buyerChannel->buyer_id,
                                            'lead_id'               => $this->id,
                                            'buyer_agent_id'        => $buyer_agent_id,
                                            'product'               => $this->product,
                                            'price_ttl'             => $ttl,
                                            'payment_id'            => null,
                                        ));   
                                    }    
                                    
                                    // запись в билинг      
                                    $this->database->insert('leads_billing',array(
                                        'idLead'            =>  $this->id,
                                        'product'           =>  $this->product,
                                        
                                        'buyerID'           =>  $buyerChannel->buyer_id,
                                        'buyerChannelID'    =>  $buyerChannel->id,
                                        
                                        'webmasterID'       =>  $this->affid,
                                        'agentID'           =>  $this->agentID,
                                        'referID'           =>  $this->refaffid,
                                        
                                        'userMoney'         =>  0,
                                        'agentMoney'        =>  0,
                                        'referMoney'        =>  0,
                                        'totalMoney'        =>  $ttl,
                                        
                                        'adminName'         =>  '.script', 
                                        'adminUserID'       =>  '0',
                                    ));
                                     
                                    // создание ретурновой записи
                                    $createDatetime = date("Y-m-d H:i:s");
                                    $this->database->insert("buyers_leads_movements", array(
                                        'action_type'               => 'reject',
                                        'lead_id'                   => $this->id,
                                        'channel_id'                => $buyerChannel->id,
                                        'buyer_id'                  => $buyerChannel->buyer_id,
                                        'invoice_id'                => null,
                                        'posting_log_record_id'     => null,
                                        'action_datetime'           => $createDatetime,
                                        'channel_action_datetime'   => $createDatetime,
                                        'action_sum'                => $ttl,
                                        'lead_email'                => $this->data_email,
                                        'lead_home_phone'           => $this->data_phone,
                                        'lead_product'              => $this->product,
                                        'is_v1_lead'                => '0',
                                        'syncId'                    => null,
                                    ));

                                    if($ttl != 0){
                                        /*
                                        Logobaza_Main::buyersLeadsMovements()->add(array(
                                            'mid'               => $this->database->lastInsertId(),     // movement id
                                            'lead_id'           => $this->id,                           // id лида
                                            'channel_id'        => $buyerChannel->id,                   // id канала, который купил лид
                                            'buyer_id'          => $buyerChannel->buyer_id,             // id покупателя, которому принадлежит канал, который купил лид
                                            'action_sum'        => $ttl,                                // сумма, которую нам должен за него баер
                                        ), $createDatetime);
                                        */
                                    }
                                    
                                    // создание ретурна для статы
                                    $returnObject = new T3Lead_Return();   
                                    $returnObject->setParams(array(
                                        'user_id'           => T3Users::getCUser()->id,
                                        'user_ip_address'   => $_SERVER['REMOTE_ADDR'],

                                        'wm_show'           => 0,
                                        'lead_id'           => $this->id,
                                        'movement_id'       => '0',
                                        'from_v1'           => '0',

                                        'product'           => $this->product,
                                        'get_method'        => $this->get_method,
                                        'channel_id'        => $this->channel_id,
                                        'subacc'            => $this->subacc,

                                        'invoiceItemType'   => 'sellings',
                                        'invoiceItemID'     => 0,
                                        'buyer'             => $buyerChannel->buyer_id,
                                        'posting'           => $buyerChannel->id,

                                        'affid'             => $this->affid,
                                        'refaffid'          => 0,
                                        'agentID'           => $this->agentID,

                                        'wm'                => 0,
                                        'ref'               => 0,
                                        'agn'               => 0,
                                        'ttl'               => $ttl,

                                        'lead_datetime'     => $this->datetime,
                                        'return_datetime'   => date("Y-m-d H:i:s"),

                                        'data_email'        => $this->data_email,
                                        'data_phone'        => $this->data_phone,
                                        'data_ssn'          => $this->data_ssn,
                                        'data_state'        => $this->data_state,

                                        'comment'           => "sell to other buyers",
                                    ));

                                    $returnObject->insertIntoDatabase();
                                    
                                    // добавление в стату вебмастров                         
                                    T3Report_Summary::addNewReturn($returnObject);  
                                    
                                    // добалвение в стату баеров
                                    T3BuyersStats::getInstance()->recordReturn($returnObject, $buyerChannel->buyer_id, $buyerChannel->id);
                                    
                                }

                                $sold = true;

                                /**
                                 * Причиной для остановки при продажи могут быть
                                 *
                                 * 1. положительная цена (и достижения максимального порога продаж)
                                 * 2. переданна редирект ссылка
                                 * 3. продажа при нуле (и включенный режим что бы при 0 останавливалось)
                                 */
                                if($stopIfSold0 || $postResult->priceTTL != 0 || strlen($postResult->redirectLink)){
                                    $solds_count++;

                                    // если есть ссылка для редиректа или количесво продаж подошло к максимуму, закончить продажу
                                    if(strlen($postResult->redirectLink) || $solds_count >= $solds_count_max){
                                        break;
                                    }
                                }
                            }
                            else if($postResult->isFiltered()){
                                // лид не подошел по филтрам
                                $filtered = true;
                            }
                            else if($postResult->isSend() && !$postResult->isSold()){
                                // Лид был доставлен, и не продан
                                $reject = true;
                            }
                            else if($postResult->isDuplicated()){
                                // Баер уже видел этот лид
                                $duplicate = true;
                            } 
                        }
                    } 
                }  
                
                // Назначение статусов
                if($sold){
                    // Лид был продан
                    $this->setStatusSold();
                    $pingtreePostResult->setSold(
                        $resultWebmasterValue, 
                        $resultRedirectLink
                    ); 
                    $pingtreePostResult->totalPrice = $resultTotalValue;     
                }
                else {
                    // лид не был продан после прохождения всех баеров из данного PingTree
                    // Возможные стутусы:
                    if($reject){
                        if(!$this->isStatusSold()){
                            $this->setStatusReject();
                        }
                        $pingtreePostResult->setReject();
                    }
                    else if($filtered){
                        if(!$this->isStatusSold() && !$this->isStatusReject() && !$this->isStatusDuplicate()) $this->setStatusReject();
                        $pingtreePostResult->setFiltered();
                    }
                    else if($duplicate){
                        if(!$this->isStatusSold() && !$this->isStatusReject()) $this->setStatusDuplicate();
                        $pingtreePostResult->setDuplicate();
                    }
                    else {
                        if($this->isStatusProcess()) $this->setStatusReject();     
                    }
                }
                
                // Положить все накопленные лиги в базу данных включить режим записи логов в реальнов времени
                T3BuyerChannel_PostDatetimes::commit();
                T3BuyerChannel_Reasons::commit();
                T3BuyerChannel_Logs::commit();
                T3SecureSubID::commit();  
                T3BuyersStats::getInstance()->commitLeadsQueue();
                T3BuyersStats::getInstance()->enqueueLeadsMode = false;
            } 
        }
        
        T3Report_FirstLook::commit(); 
        
        // сохранить массив данных об отправке
        $this->postingsSenedAutoSave = true;
        $this->savePostingsSended();    

        $this->saveToDatabase();
        
        return $pingtreePostResult;
    }
    
    /**
    * Переменная показывает, в каком режиме находится лид для отправки
    * null - неизвестно (отправка через интерфейс или нестандартное место)
    * true - лид перепостывается
    * false - лид только что пришел
    * 
    * @var mixed
    */
    protected $is_repost = null;
    
    public function setIsReport($isRepost){
        $this->is_repost = (bool)$isRepost;
    }
    
    /**
    * Отправка лида По Умолчанию. 
    * 1. Подбор PingTree
    * 2. Если PingTree найден, отправка на баеров от этого PingTree
    * 
    * @return T3Lead_PingtreePostResult Результат отправки по PingTree
    */
    public function postDefault(){
        // поиск и загрузка Pingtree для этого лида
        $pingTree = new T3PingTree($this);

        if(!$pingTree->id){
            $pingTree->loadAllActiveChannels($this->product);
        }

        if(count($pingTree->order)){
            // Продажа лида на найденный Pingtree
            return $this->postToPingtree($pingTree);
        }
        else {   
            /**
            * Не найден PingTree, для Payday лидов Это ошибка!
            * 1. Логировать ошибку
            * 2. Создать объект ответа с ответом Pending (что значит что лид был продан, и возможно скоро будет продан)
            */
            if($this->isStatusProcess()){
                $this->setStatusNoSend();
                $this->saveToDatabase();
            }

            // перед добавлением ошибки можно проверять обязательно ли для этого продукта pingtree, иначе будет много лишних ошибок
            //T3ConfigErrorsReporting::addError("PingTreeNotFound", "Lead ID: {$this->id}");
            return T3Lead_PingtreePostResult::newObject()->setNotPingTree();
        } 
    }
    
    /**
    * Возвращает массив оплаты текущего лида
    * 
    * @return array
    */
    public function getBilling_Array(){
        if($this->id){
            return T3Leads::getInstance()->getBilling_Array($this->id);
        }
        return null;  
    }

    public function getBuyerChannelsSawLead(){
        return array();
    }
  
    /**
    * Получение логов отправки лида
    * 
    * @param mixed $condition
    * @return array
    */
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
        $where.="leadID=".$this->id;
        
        $tables = $this->database->fetchAll("select table_name from information_schema.tables where table_name rlike 'buyers1_channels_post_results'");
        
        $query = "";
        $n = count($tables);
        for ($i=0;$i<$n;$i++){
            $query.=sprintf("(SELECT * FROM %s WHERE %s)",$tables[$i]['table_name'],$where);
            if ($i<($n-1)){
                $query.=" UNION ";    
            }    
        }
        
        return $this->database->fetchAll($query);  
        */  
    }
    
    /**
    * Получение URL фида для баера
    * Идет 100% Hard Code
    */
    public function getSiteURL_for_Buyer(){

        $site = $this->getRegisterValue("_sitesURL_HardCode_");
        
        if(!$site){
            $arr = array();
            
            if(isset($this->body->_sitesURLs) && is_array($this->body->_sitesURLs) && count($this->body->_sitesURLs)){
                foreach($this->body->_sitesURLs as &$url){
                    if(is_string($url) && strlen($url)){
                        $arr[] = $url;        
                    }
                }        
            }
            
            if(!count($arr)){
                $arr = array('t3leads.com');
            }
            
            $site = $arr[rand(0, count($arr)-1)];
            
            $this->setRegisterValue("_sitesURL_HardCode_", $site);
        }
        
        return $site;    
    }
    
    public function fromDatabase($conditions, $fillObjects = array()) {
        $result = parent::fromDatabase($conditions, $fillObjects);
        $this->postingsSened = $this->getRegisterValue("postingsSened", array());
        return $result;   
    }
    
    
    public function loadFromGetID($id){
        if((int)$id == $id){
            /**
            * @var T3User
            */
            $user = T3Users::getCUser();
            if(is_object($user) && $user instanceof T3User && $user->id && $user->isRoleAdmin()){
                $this->fromDatabase($id);     
            }       
        }
        else {
            $getID = new T3Leads_GetID($id, false);
            
            if($getID->rolingParserGood){
                $this->fromDatabase(array(
                    'affid'  => $getID->wm,
                    'num' => $getID->num,
                )); 
            }
        }    
    }
    
    
    
    public function isStatusSold()          { return ($this->status == 'sold');         } 
    public function isStatusReject()        { return ($this->status == 'reject');       } 
    public function isStatusError()         { return ($this->status == 'error');        } 
    public function isStatusDuplicate()     { return ($this->status == 'duplicate');    } 
    public function isStatusPending()       { return ($this->status == 'pending');      } 
    public function isStatusTimeout()       { return ($this->status == 'timeout');      } 
    public function isStatusProcess()       { return ($this->status == 'process');      } 
    public function isStatusVerification()  { return ($this->status == 'verification'); } 
    public function isStatusNoConect()      { return ($this->status == 'noconect');     } 
    public function isStatusNoSend()        { return ($this->status == 'nosend');       } 
    
    public function setStatusSold()         { $this->status = 'sold';         } 
    public function setStatusReject()       { $this->status = 'reject';       } 
    public function setStatusError()        { $this->status = 'error';        } 
    public function setStatusDuplicate()    { $this->status = 'duplicate';    } 
    public function setStatusPending()      { $this->status = 'pending';      } 
    public function setStatusTimeout()      { $this->status = 'timeout';      } 
    public function setStatusProcess()      { $this->status = 'process';      } 
    public function setStatusVerification() { $this->status = 'verification'; } 
    public function setStatusNoConect()     { $this->status = 'noconect';     } 
    public function setStatusNoSend()       { $this->status = 'nosend';       } 
    
    
    public function getPostingsForPostToBuyer(T3Lead $lead)
    {
        if( !$lead instanceof T3Lead ) throw new Exception('Param is not instanceof T3Lead');
        
        $select = T3Db::api()->select();
        
        $select
        ->from("buyers_channels")
        ->where("product=?", $lead->product)
        ->where("`status` in ('active','paused')");
            
        $channels = T3Db::api()->fetchAll($select);       
            $lead_send_channels_tmp = T3BuyerChannel::getChannelsList( $lead->id );
            $lead_send_channels = array();
            if(count($lead_send_channels_tmp)) {
                foreach ( $lead_send_channels_tmp As $k=>$v ) {
                    $lead_send_channels[$v['buyerChannelID']] = $v;
                }
            }

        $lead->getBodyFromDatabase();
        
                foreach ( $channels AS $ck=>$cv ) {
                $posting = new T3BuyerChannel();
				
				$filter = T3BuyerFilters::getInstance()->getFilter($cv['id']);
				$filterResult = $filter->acceptsLead( $lead );
				if($filterResult->isError()){
					$cv['filterError'] = $filter->getTextReport();
				} else {
					$cv['filterStatus'] = 'Ok';
				}
				
				$posting->fromDatabase($cv['id']);
				$rule = T3PriceRule::newObject()->searchRule($lead, $posting )->runProcents();
                $cv['priceWM'] = $rule->priceWM;
                $cv['priceAgent'] = $rule->priceAgent;
                $cv['priceReferal'] = $rule->priceReferal;
                $cv['priceT3Leads'] = $rule->priceT3Leads;
                $cv['priceTTL'] = $rule->priceTTL;
                $channels[$ck] = $cv;
                unset($posting);
            }
        
        
        return array(
            'channels'            => $channels,
            'lead_send_channels'  => $lead_send_channels,
        ); 
    }
    
    public function postOrTestToBuyer($lead_id, $posting_id, $isTest = false)
    {
        $lead = new T3Lead();
        $lead->fromDatabase($lead_id);
               
        $postResult = $lead->postToBuyer($posting_id, $isTest);
        
        return $postResult;
    }
    
    public function getMinPrice(){
        return $this->minPrice;
    }
    
    /**
    * Отправка лида в call центр
    * предварительно проверить, если лид уже 1 раз проверялся, то его не надо проверять повторно 
    * 
    */
    public function vefification(){
        //$this->setStatusVerification();
        //$this->saveToDatabase();   
    }
    
    /**
    * Репорт от call центра по этому лиду
    * 
    * @return T3CallCenter_LeadMinResult
    */
    /*
    public function isCallCenterResult(){
        $result = new T3CallCenter_LeadMinResult();
        $result->status = 'no';
        
        return $result; 
    }
    */
}

