<?php

class T3Payments {

    const PART_LEADS                = 'leads';
    const PART_BONUSES              = 'bonuses';
    const PART_BALANCE_V1           = 'old_leads';
    const PART_MOVEMENTS            = 'movements';
    const PART_MOVEMENTS_V1         = 'movements_v1';
    const PART_REFERRALS            = 'referrals';
    const PART_CLICKS               = 'clicks';
    const PART_SORA                 = 'sora';

    const DEFAULT_HOLD_LEADS        = 14;
    const DEFAULT_HOLD_BONUSES      = 14;
    const DEFAULT_HOLD_BALANCE_V1   = 14;
    const DEFAULT_HOLD_MOVEMENTS    = 14;
    const DEFAULT_HOLD_MOVEMENTS_V1 = 14;
    const DEFAULT_HOLD_REFERRALS    = 14;
    const DEFAULT_HOLD_CLICKS       = 14;
    const DEFAULT_HOLD_SORA         = 14;

    const PERIOD_TYPE_WEEKLY        = 'weekly';
    const PERIOD_TYPE_BIWEEKLY      = 'biweekly';
    const PERIOD_TYPE_MONTHLY       = 'monthly';
    const PERIOD_TYPE_TWICEMONTHLY  = 'twicemonthly';
    const PERIOD_TYPE_MANUAL_ONLY   = 'manual_only';

    const SYS_CHECK                 = 'check';
    const SYS_EPASS                 = 'epass';
    const SYS_PAYPAL                = 'paypal';
    const SYS_WEBMONEY              = 'webmoney';
    const SYS_WIRE                  = 'wire';
    const SYS_FETHARD               = 'fethard';
    const SYS_ACH_3_BUSINESS_DAYS   = 'ach_3_business_days';
    const SYS_ACH_NEXT_BUSINESS_DAY = 'ach_next_business_day';
    const SYS_ACH_SAME_DAY          = 'ach_same_day';

    const APPR_REQUEST_first_payments           = 'first_payments';
    const APPR_REQUEST_fraud_detector_request   = 'fraud_detector_request';

    protected static $_instance;
    protected $database;
    protected $systemsData;
    protected $webmastersSystemNames;
    protected $needToTakeFeeFromResult;
    protected $defaultHolds;
    protected $defaultFee;
    protected $minimumOfAllSystems;
    protected $lastWebmastersPaymentsSystemsResult;
    protected $lastWebmastersPaymentsSystemsIds;
    protected $lastWebmastersPaymentsPeriodsResult;
    protected $lastWebmastersPaymentsPeriodsIds;
    public $maturedPaymentsCache;
    public $incomeByDatesCache;
    protected $nextSuccessiveIds;
    public static $paymentDetailsConditions = array(
        'all' => array(
            'action_datetime',
            'lead_product',
            'channel_id',
        ),
        'webmasters_bonuses' => array(
            'channel_id' => 'lead_channel_id',
        ),
        'webmasters_old_leads' => array(
            'lead_product' => null,
            'channel_id' => null,
        ),
        'webmasters_clicks' => array(
            'lead_product' => null,
            'channel_id' => null,
        ),
        'webmasters_sora_leads' => array(
            'lead_product' => null,
            'channel_id' => null,
        ),
    );
    public static $tablesConnections = array(
        'all' => array(
            'item_type',
            'lead_id',
            'channel_id',
            'subaccount_id',
            'action_datetime',
            'action_sum',
            'lead_email',
            'lead_ssn',
            'lead_home_phone',
            'lead_product',
            'lead_is_from_v1',
        ),
        'webmasters_bonuses' => array(
            'item_type' => "('Bonuses')",
            'channel_id' => 'lead_channel_id',
            'subaccount_id' => "('')",
            'lead_email' => "('')",
            'lead_ssn' => "('')",
            'lead_home_phone' => "('')",
            'lead_product' => "('')",
            'lead_is_from_v1' => '(0)',
        ),
        'webmasters_old_leads' => array(
            'item_type' => "('T3Ver.1')",
            'lead_id' => "('')",
            'channel_id' => "('')",
            'subaccount_id' => "('')",
            'lead_email' => "('')",
            'lead_ssn' => "('')",
            'lead_home_phone' => "('')",
            'lead_product' => "('')",
            'lead_is_from_v1' => '(1)',
        ),
        'webmasters_leads_sellings' => array(
            'item_type' => "('All Leads')",
            'lead_is_from_v1' => '(0)',
        ),
        'webmasters_leads_movements' => array(
            'item_type' => "('Return')",
            'lead_is_from_v1' => '(0)',
        ),
        'webmasters_leads_movements_v1' => array(
            'item_type' => "('Return V1')",
            'lead_is_from_v1' => '(1)',
        ),
        'webmasters_clicks' => array(
            'item_type' => "('Clicks')",
            'lead_id' => "('')",
            'channel_id' => "('')",
            'subaccount_id' => "('')",
            'lead_email' => "('')",
            'lead_ssn' => "('')",
            'lead_home_phone' => "('')",
            'lead_product' => "('')",
            'lead_is_from_v1' => '(0)',
        ),
        'webmasters_sora_leads' => array(
            'item_type' => "('Backend')",
            'lead_id' => "('')",
            'channel_id' => "('')",
            'subaccount_id' => "('')",
            'lead_email' => "('')",
            'lead_ssn' => "('')",
            'lead_home_phone' => "('')",
            'lead_product' => "('')",
            'lead_is_from_v1' => '(0)',
        ),
    );
    public static $bodyExportParts = array(
        T3Payments::PART_LEADS,
        T3Payments::PART_MOVEMENTS,
        T3Payments::PART_MOVEMENTS_V1,
    );
    public static $partsData = array(
        T3Payments::PART_LEADS => array(
            'title' => 'All Leads',
            'tables' => array('webmasters_leads_sellings',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_LEADS,
        ),
        T3Payments::PART_MOVEMENTS => array(
            'title' => 'Movements',
            'tables' => array('webmasters_leads_movements',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_MOVEMENTS,
        ),
        T3Payments::PART_MOVEMENTS_V1 => array(
            'title' => 'Movements V1',
            'tables' => array('webmasters_leads_movements_v1',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_MOVEMENTS_V1,
        ),
        T3Payments::PART_BONUSES => array(
            'title' => 'Bonuses',
            'tables' => array('webmasters_bonuses'),
            'defaultHold' => T3Payments::DEFAULT_HOLD_BONUSES,
        ),
        T3Payments::PART_BALANCE_V1 => array(
            'title' => 'T3Ver.1',
            'tables' => array('webmasters_old_leads',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_BALANCE_V1,
        ),
        T3Payments::PART_REFERRALS => array(
            'title' => 'Referral Addings',
            'tables' => array('webmasters_referral_addings',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_REFERRALS,
        ),
        T3Payments::PART_CLICKS => array(
            'title' => 'Clicks',
            'tables' => array('webmasters_clicks',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_CLICKS,
        ),
        T3Payments::PART_SORA => array(
            'title' => 'Backend',
            'tables' => array('webmasters_sora_leads',),
            'defaultHold' => T3Payments::DEFAULT_HOLD_SORA,
        ),
    );
    public static $periodTypesData = array(
        T3Payments::PERIOD_TYPE_WEEKLY => array(
            'name' => T3Payments::PERIOD_TYPE_WEEKLY,
            'title' => 'Weekly',
        ),
        T3Payments::PERIOD_TYPE_BIWEEKLY => array(
            'name' => T3Payments::PERIOD_TYPE_BIWEEKLY,
            'title' => 'BiWeekly',
        ),
        T3Payments::PERIOD_TYPE_MONTHLY => array(
            'name' => T3Payments::PERIOD_TYPE_MONTHLY,
            'title' => 'Monthly',
        ),
        T3Payments::PERIOD_TYPE_TWICEMONTHLY => array(
            'name' => T3Payments::PERIOD_TYPE_TWICEMONTHLY,
            'title' => 'TwiceMonthly',
        ),
        T3Payments::PERIOD_TYPE_MANUAL_ONLY => array(
            'name' => T3Payments::PERIOD_TYPE_MANUAL_ONLY,
            'title' => 'Manual Payment',
        ),
    /* '0'=>array(
      'name' => '0',
      'title' => '',
      ),
      ''=>array(
      'name' => '',
      'title' => '',
      ), */
    );
    public static $periodTypesTitles = array(/**/);
    public static $parts = array(/**/);
    public static $partsTables = array(/**/);

    static public function getSystemsTitles() {
        return array(
            self::SYS_CHECK => 'Check',
            //self::SYS_EPASS     => 'Epassporte',
            self::SYS_PAYPAL => 'Paypal',
            self::SYS_WEBMONEY => 'Webmoney',
            self::SYS_WIRE => 'Wire Transfer',
            self::SYS_ACH_3_BUSINESS_DAYS => 'ACH 3 Business Days',
            self::SYS_ACH_NEXT_BUSINESS_DAY => 'ACH Next Business Day',
            self::SYS_ACH_SAME_DAY => 'ACH Same Day',
        );
    }

    static public function getCurrentsPaymentsPeriods() {
        $currency = new Zend_Currency('en_US');

        $current_periods = array(
            '2010-03-29,weekly' => 'Mar 29, 2010 - Weekly - 10 Webmasters',
            '2010-04-01,twicemonthly' => 'Apr 01, 2010 - Twice Monthly - 54 Webmasters',
            '2010-04-01,monthly' => 'Apr 01, 2010 - Monthly - 1 Webmaster',
            '345' => 'maderito: $ 2,300.34 - Special Payment (Apr 03, 2010)', // ключ - ID пеймента 
        );

        $result = array();
        $temp = array();

        $payments = T3Db::api()->fetchAll("

      select wp.id, wp.webmaster_id, wp.total_value_without_fee,
      DATE_FORMAT(wp.period_formal_end,'%Y-%m-%d') as DateEnd_FormatYmd,wp.period_type,wp.created_specially
      from webmasters_payments wp

      left join users_company_webmaster ucw on wp.webmaster_id = ucw.id

      where wp.fully_paid='0' && ucw.status = 'activ'
      order by wp.created_specially, wp.period_formal_end, wp.period_type

    ");

        if (count($payments)) {
            foreach ($payments as $payment) {
                if ($payment['created_specially']) {
                    $result[$payment['id']] = T3WebmasterCompanys::getCompany($payment['webmaster_id'])->systemName . ": " . $currency->toCurrency($payment['total_value_without_fee']) .
                    " - Special Payment (" . DateFormat::dateOnly($payment['DateEnd_FormatYmd']) . ")";
                } else {
                    $key = $payment['DateEnd_FormatYmd'] . "," . $payment['period_type'];
                    if (!isset($temp[$key])) {
                        $temp[$key] = array(DateFormat::dateOnly($payment['DateEnd_FormatYmd']) . " - " . self::$periodTypesData[$payment['period_type']]['title'] . " - ", 1, " Webmaster");
                        $result[$key] = "";
                    } else {
                        $temp[$key][1]++;
                        $temp[$key][2] = " Webmasters";
                    }
                }
            }

            foreach ($temp as $key => $opts) {
                $result[$key] = implode("", $opts);
            }
        }

        return $result;
    }

    static public function notificationCurrnetPayments() {
        AZend_Notifications::deleteNotificationToUser(
        "payments", "0", "unpaidCurrent"
        );


        if (T3Db::api()->fetchOne("select count(*) from webmasters_payments where fully_paid=0")) {
            $ids = T3System::getValue('paymentsNotificationsUsersIds');

            if (is_array($ids) && count($ids)) {
                foreach ($ids as $id) {
                    AZend_Notifications::addNotificationToUser(
                    $id, T3System::getValue("paymentsNotificationsTitle", "Unpaid current payments"), "", "/en/account/payments/current-payment-list", "payments", "0", "unpaidCurrent"
                    );
                }
            }
        }
    }

    public function getAvailableSystems() {
        return array(
            T3Payments::SYS_CHECK,
            //T3Payments::SYS_EPASS,
            T3Payments::SYS_PAYPAL,
            T3Payments::SYS_WEBMONEY,
            T3Payments::SYS_WIRE,
            T3Payments::SYS_ACH_3_BUSINESS_DAYS,
            T3Payments::SYS_ACH_NEXT_BUSINESS_DAY,
            T3Payments::SYS_ACH_SAME_DAY,
        //T3Payments::SYS_FETHARD,
        );
    }

    public function getSystemsData() {
        if ($this->systemsData !== null){
            return $this->systemsData;
        }
        
        $a = groupBy(
            $this->database->fetchAll('SELECT * FROM payment_types where active'), 
            null, 
            'pay_type'
        );
        
        $b = $this->getAvailableSystems();
        
        $this->systemsData = $a;
        
        foreach ($a as $k => $v){
            if (!in_array($k, $b)){
                unset($this->systemsData[$k]);
            }
        }
        
        return $this->systemsData;
    }

    static $mininumPaymentsInT3Leads;
    
    static public function getMinimumOfAllSystems() {
        if(is_null(self::$mininumPaymentsInT3Leads)){
            self::$mininumPaymentsInT3Leads = (int)T3Db::api()->fetchOne("select min(minimal) from payment_types where active='1' limit 1");
        }
        return self::$mininumPaymentsInT3Leads;
    }

    public function getSeveralPayments_Array(array $ids) {
        if (empty($ids)){
            return array();
        }
            
        $list = dbQuote($this->database, $ids);
        
        return $this->database->fetchAll("SELECT * FROM webmasters_payments WHERE id IN ({$list})");
    }

    protected function __construct() {

        $this->database = T3Db::api();

        T3Payments::$parts = array_keys(T3Payments::$partsData);
        foreach (T3Payments::$parts as $part)
            T3Payments::$partsTables[$part] = T3Payments::$partsData[$part]['tables'];

        foreach (T3Payments::$periodTypesData as $period)
            T3Payments::$periodTypesTitles[$period['name']] = $period['title'];

        $defaultHolds = $this->getDefaultHolds();
        foreach ($defaultHolds as $part => $hold) {
            T3Payments::$partsData[$part]['defaultHold'] = $hold;
        }
    }

    /** @return T3Payments */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    
    /***************************************************************************************************************/
    
    /**
    * Массив одъектов T3Payments_Systems
    * 
    * @var array
    */
    static protected $paymentsSystemsCache = array();
    
    /**
    * Загрузить в кеш систем оплаты необходимые объекты
    * Групповое получение данных из базы 
    * 
    * @param mixed $webmastersIDs array(webmasterID_1, $webmasterID_2, ...)
    */
    static public function loadSystems($webmastersIDs){
        if(is_array($webmastersIDs) && count($webmastersIDs)){
            $all = T3Db::api()->fetchAll("SELECT * FROM webmasters_payments_systems WHERE webmaster_id in ('" . implode("','", $webmastersIDs) . "')");
            
            $temp = array();
            if(count($all)){
                foreach($all as $el){
                    $temp[$el['webmaster_id']] = $el;        
                }
            }    
            
            foreach($webmastersIDs as $wmid){
                self::$paymentsSystemsCache[$wmid] = new T3Payments_Systems();
                
                if(isset($temp[$wmid])){
                    self::$paymentsSystemsCache[$wmid]->fromArray($temp[$wmid]);
                    self::$paymentsSystemsCache[$wmid]->existsInDatabase = true;      
                }  
                else {
                    self::$paymentsSystemsCache[$wmid]->fillDefault($wmid);     
                }
            }
        }
    }
    
    /** 
    * Загрузить объект системы оплаты
    * 
    * @param mixed $webmasterId
    * @return T3Payments_Systems
    */
    static public function getSystems($webmasterId) {
        if(!isset(self::$paymentsSystemsCache[$webmasterId])){
            self::$paymentsSystemsCache[$webmasterId] = new T3Payments_Systems();
            
            // если объект не найден в базе, записать в него значения по умолчанию
            if (self::$paymentsSystemsCache[$webmasterId]->fromDatabase($webmasterId) === false){
                self::$paymentsSystemsCache[$webmasterId]->fillDefault($webmasterId);
            }
        }
        
        return self::$paymentsSystemsCache[$webmasterId];
    }
    
    /***************************************************************************************************************/

    static protected $cacheHolds = array();
    
    /**
    * Грапповая загрузка холодов
    * 
    * @param mixed $webmastersIDs array(webmasterID_1, $webmasterID_2, ...)
    */
    static public function loadHolds($webmastersIDs){
        if(is_array($webmastersIDs) && count($webmastersIDs)){
            $all = T3Db::api()->fetchAll("SELECT * FROM webmasters_payments_holds WHERE webmaster_id in ('" . implode("','", $webmastersIDs) . "')");

            if(count($all)){
                foreach($all as $el){
                    self::$cacheHolds[$el['webmaster_id']] = new T3Payments_Holds();
                    
                    self::$cacheHolds[$el['webmaster_id']]->fromArray($el);
                    self::$cacheHolds[$el['webmaster_id']]->webmaster_id = $el['webmaster_id'];    
                }
            }
            
            // значения, которых нет в базе данных, заполнить болванками
            foreach($webmastersIDs as $webmasterId){
                if(!isset(self::$cacheHolds[$webmasterId])){
                    self::$cacheHolds[$webmasterId] = new T3Payments_Holds();
                    self::$cacheHolds[$webmasterId]->webmaster_id = $webmasterId;
                }      
            }
        }
    }
    
    static public function getHolds($webmasterId) {
        if(!isset(self::$cacheHolds[$webmasterId])){
            self::$cacheHolds[$webmasterId] = new T3Payments_Holds();
            if (self::$cacheHolds[$webmasterId]->fromDatabase($webmasterId) === false) {
                self::$cacheHolds[$webmasterId] = new T3Payments_Holds();
                self::$cacheHolds[$webmasterId]->webmaster_id = $webmasterId;
            }     
        }
        
        return self::$cacheHolds[$webmasterId];
    }
    
    /***************************************************************************************************************/

    public function getDefaultFee($lazy = true) {
        if ($lazy && $this->defaultFee !== null)
            return $this->defaultFee;
        $this->defaultFee = $this->database->fetchOne('SELECT fee FROM webmasters_payments_holds WHERE webmaster_id = 0');
        if ($this->defaultFee === false) {
            // TODO
            // ошибка для администратора
        }
        return $this->defaultFee;
    }

    public function getDefaultHolds($lazy = true) {
        if ($lazy && $this->defaultHolds !== null)
            return $this->defaultHolds;
        
        $row = $this->database->fetchOne('SELECT holds FROM webmasters_payments_holds WHERE webmaster_id = 0');
        
        $this->defaultHolds = unserialize($row);
        if ($this->defaultHolds === false) {
            // TODO
            // ошибка для администратора
        }
        foreach (T3Payments::$partsData as $part => $data)
            if (!isset($this->defaultHolds[$part]))
                $this->defaultHolds[$part] = $data['defaultHold'];
        return $this->defaultHolds;
    }

    /*******************************************************************************/
    
    static protected $cachePaymentsRealEnds;
    
    public function getLastPaymentRealEnds($webmasterId) {
        
        if(!is_array(self::$cachePaymentsRealEnds)){
            self::$cachePaymentsRealEnds = array();
            $all = T3Db::api()->fetchAll(
                "select * from (SELECT webmaster_id, period_formal_end, period_real_ends FROM webmasters_payments " .    
                "WHERE not created_specially order by period_formal_end desc) as tmp " .
                "group by webmaster_id"
            );
            
            if(count($all)){
                foreach($all as $el){
                    self::$cachePaymentsRealEnds[$el['webmaster_id']] = $el; 
                }
            }    
        }
        
        // подстраиваем кеш под предыдущую функиональность
        $a = false;
        if(isset(self::$cachePaymentsRealEnds[$webmasterId])){
            $a = self::$cachePaymentsRealEnds[$webmasterId];     
        } 

        if (empty($a)) {    
            $minmin = T3WebmasterCompanys::getCompany($webmasterId)->reg_date;
            
            $value = array();
            foreach (T3Payments::$parts as $part){
                $value[$part] = $minmin;
            }
                
            $value[self::PART_BONUSES] = $value[self::PART_LEADS];
            $value['period_formal_end'] = $minmin;
            
            if ($minmin === null) {
                // TODO ошибка для администратора
            }
            else {
                foreach ($value as $k => $v){
                    if ($v === null){
                        $value[$k] = $value['period_formal_end'];
                    }
                }
            }
        }
        else {
            $value = T3Payment::readArray($a['period_real_ends']);

            $b1 = false;
            foreach ($value as $v){
                if (empty($v)) {
                    $b1 = true;
                    break;
                }
            }
            
            if ($b1) {
                $minmin = T3WebmasterCompanys::getCompany($webmasterId)->reg_date;
                foreach ($value as $k => $v){
                    if (empty($v)) {
                        $value[$k] = $minmin;
                    }
                }
            }

            $value['period_formal_end'] = $a['period_formal_end'];
        }
        return $value;
    }
    
    /****************************************************************************************/

    public function getNextDateForAll($periodType, $firstDateOfPeriod = null) {
        if ($firstDateOfPeriod !== null) {
            $nowZd = new Zend_Date($firstDateOfPeriod, MYSQL_DATETIME_FORMAT_ZEND);
            $nowZd->addDay(1);
        }else
            $nowZd = new Zend_Date();
        switch ($periodType) {
            case T3Payments::PERIOD_TYPE_WEEKLY:
                $nowZd->addWeek(1);
                $nowZd->setWeekday(1);
                break;
            case T3Payments::PERIOD_TYPE_BIWEEKLY:
                $nowZd->addWeek(2);
                $nowZd->setWeekday(1);
                break;
            case T3Payments::PERIOD_TYPE_TWICEMONTHLY:
                $day = $nowZd->get(Zend_Date::DAY);
                if ($day >= 1 && $day < 15) {
                    $nowZd->setDay(15);
                } elseif ($day >= 15) {
                    $nowZd->addMonth(1);
                    $nowZd->setDay(1);
                }
                break;
            case T3Payments::PERIOD_TYPE_MONTHLY:
                $nowZd->addMonth(1);
                $nowZd->setDay(1);
                break;
            default:
                return false;
        }
        $nowZd->setTime('00:00:00');
        return $nowZd;
    }

    public function getNextPaymentPayDate(T3WebmasterCompany $webmaster) {

        if ($webmaster->payPeriod == T3Payments::PERIOD_TYPE_MANUAL_ONLY) {
            return array(
                'type' => 'manual',
            );
        }

        $payment_data = $this->database->fetchRow(
            'select * from webmasters_payments where webmaster_id = ? order by creation_datetime desc limit 1', 
            array($webmaster->id)
        );

        $thereIsDate = false;

        if (empty($payment_data)) {

            $zd = new Zend_Date();
            $zd->setTime("00:00:00");
            $zd = $this->getNextDateForAll($webmaster->payPeriod, $zd->toString(MYSQL_DATETIME_FORMAT_ZEND));
            if (!empty($zd)) {
                $thereIsDate = true;
                $date = $zd;
                $ar = array(
                    'type' => 'expected',
                );
            }
        } else if ($payment_data['fully_paid']) {

            $zd = new Zend_Date($payment_data['period_formal_end'], MYSQL_DATETIME_FORMAT_ZEND);
            $zd->addDay(1);

            $zdNow = new Zend_Date();
            $zdNow->setTime("00:00:00");
            if ($zd->isEarlier($zdNow))
                $zd = $zdNow;

            $zd = $this->getNextDateForAll($webmaster->payPeriod, $zd->toString(MYSQL_DATETIME_FORMAT_ZEND));


            if (!empty($zd)) {
                $thereIsDate = true;
                $date = $zd;
                $ar = array(
                    'type' => 'expected',
                );
            }
        } else {

            $thereIsDate = true;
            $date = new Zend_Date($payment_data['period_formal_end'], MYSQL_DATETIME_FORMAT_ZEND);
            $ar = array(
                'type' => 'created',
                'amount' => $payment_data['total_value_without_fee'],
            );
        }

        if ($thereIsDate) {

            for ($step = 0; $step < 7; $step++, $date->addDay(1)) {

                $day = $date->get(Zend_Date::WEEKDAY_DIGIT);

                if ($day == 6 || $day == 0)
                    continue;

                if (Holidays::isHolidayBool($date->toString(MYSQL_DATE_FORMAT_ZEND)))
                    continue;

                break;
            }

            $ar['date'] = $date->toString(MYSQL_DATE_FORMAT_ZEND);
            $ar['dateStr'] = DateFormat::dateOnly($ar['date']);
        }

        return $ar;
    }

    public function webmasterExists($id) {
        return $this->database->fetchOne('
      SELECT count(*)
      FROM users_company_webmaster
      WHERE id = ?
    ', array($id)) != 0;
    }

    public function getPayments_RawSelect() {
        return $this->database->select()
        ->from('webmasters_payments', array('*', 'id_for_webmaster' => "concat(successive_id, '.', webmaster_id)"))
        ->joinLeft('users_company_webmaster', 'webmasters_payments.webmaster_id = users_company_webmaster.id', array('webmaster_system_name' => 'systemName', 'companyName', 'payPeriod', 'webmaster_status' => 'status'))
        ->joinLeft("users", "users_company_webmaster.id = users.company_id", array('webmaster_full_name' => "concat(first_name, ' ', last_name)"));
    }

    public function getPayments_Array($conditions = array(), $order = array()) {
        $select = $this->getPayments_RawSelect();
        T3SimpleDbSelect::adjustStatic($select, $conditions, $order);
        return $this->database->query($select)->fetchAll();
    }

    public function getPaymentFormalEnd($webmaster_id) {
        /* $result = $this->database->fetchOne('
          SELECT period_formal_end
          FROM webmasters_payments
          WHERE webmaster_id = ?
          GROUP BY webmaster_id
          HAVING period_formal_end = max(period_formal_end)
          ', array($webmaster_id)); */

        $result = $this->database->fetchOne('
      SELECT max(period_formal_end)
      FROM webmasters_payments
      WHERE webmaster_id = ?
    ', array($webmaster_id));


        if ($result !== false)
            return $result;


        return
        $result = $this->database->fetchOne('
        SELECT reg_date as period_formal_end
        FROM users_company_webmaster
        WHERE id = ?
      ', array($webmaster_id));
    }

    public function getDateIncome($webmasterId, $date) {

        if ($this->incomeByDatesCache === null || !array_key_exists($webmasterId, $this->incomeByDatesCache))
            $this->incomeByDatesCache[$webmasterId] = $this->getIncomeByDates($webmasterId);
        if (count($this->incomeByDatesCache[$webmasterId]) == 0)
            return 0;
        if (array_key_exists($date, $this->incomeByDatesCache[$webmasterId]))
            return $this->incomeByDatesCache[$webmasterId][$date];

        $keys = array_keys($this->incomeByDatesCache[$webmasterId]);

        if ($date < $keys[0])
            return 0.0;
        else
            return $this->incomeByDatesCache[$webmasterId][$keys[count($keys) - 1]];
    }

    public function getIncomeByDates($webmasterId) {

        $s1 = "";
        $s2 = "";
        $webmasterId = (int) $webmasterId;
        $holds = new T3Payments_Holds();
        $holds->fromDatabase($webmasterId);
        $zd = new Zend_Date();
        foreach (T3Payments::$partsData as $part => $v1) {
            $hold = $holds->getHold($part);
            foreach ($v1['tables'] as $table) {
                $s1 = $s2 . " select action_sum, action_datetime + interval $hold day as action_datetime from `$table` where (payment_id is null or payment_id=0) and webmaster_id = '$webmasterId' ";
                $s2 = $s1 . " union all ";
            }
        }
        $ar = $this->database->fetchAll("
      select sum(action_sum) as sm, date(action_datetime) as dt from ($s1) as tmp1 group by dt order by dt
    ");

        if (count($ar) == 0)
            return array();

        $needToTakeFee = $this->needToTakeFeeFrom($webmasterId);
        if ($needToTakeFee)
            $systems = $this->getSystems($webmasterId);

        $sum = 0;
        $result = array();
        $zd = new Zend_Date();
        $arrayKeys = array_keys($ar);
        $i = 0;

        foreach ($ar as $k => $v) {
            try{
                $zd->set($v['dt'], MYSQL_DATE_FORMAT_ZEND);
            }
            catch(Exception $e){
                $zd->set(date("Y-m-d H:i:s"), MYSQL_DATE_FORMAT_ZEND);
            }

            $sum += $v['sm'];

            if (!$needToTakeFee) {
                $actualSum = $sum;
            } else {
                $systems->split($sum, $withFee, $withoutFee, $fee);
                $actualSum = array_sum($withoutFee);
            }

            $zdString = $zd->toString(MYSQL_DATE_FORMAT_ZEND);
            $result[$zdString] = $actualSum;

            if (array_key_exists($i + 1, $arrayKeys)) {
                $next = $ar[$arrayKeys[$i + 1]]["dt"];
                while ($zdString != $next) {
                    $zd->addDay(1);
                    $zdString = $zd->toString(MYSQL_DATE_FORMAT_ZEND);
                    $result[$zdString] = $actualSum;
                }
            }

            $i++;
        }

        return $result;
    }

    public function changeLeadActionSum($leadId, $actionSum, $table) {

        $table = 'webmasters_old_leads';

        try {

            $this->database->beginTransaction();

            $paymentId = $this->database->fetchOne("
          select payment_id from $table
          where id = ?
        ", array($leadId));

            $this->database->query("
          update $table
          set action_sum = ?
          where id = ?
        ", array($actionSum, $leadId));

            $payment = new T3Payment();
            if ($payment->fromDatabase($paymentId) !== false) {
                $payment->calcSums();
                $payment->saveToDatabase();
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }

        return true;
    }

    public function excludeFromPayment($leadsArray) {

        $tables = array(
            'webmasters_old_leads',
        );

        $ids = array();
        foreach ($leadsArray as $v) {
            if (!in_array($v->item_type, $tables))
                continue;
            if (!isset($ids[$v->item_type]))
                $ids[$v->item_type] = array();
            $ids[$v->item_type][] = $v->item_id;
        }

        try {

            $this->database->beginTransaction();

            $paymentsToRecalc = array();
            foreach ($ids as $k => $v) {
                $quotedList = dbQuote($this->database, $v);
                $paymentsToRecalc = array_merge($paymentsToRecalc, $this->database->fetchCol("
            select distinct payment_id
            from {$k}
            where id in ($quotedList)
          "));
                $this->database->query("
            update {$k}
            set payment_id = null
            where id in ($quotedList)
          ");
            }

            $paymentsToRecalc = array_unique($paymentsToRecalc);

            foreach ($paymentsToRecalc as $id) {
                $payment = new T3Payment();
                if ($payment->fromDatabase($id) === false)
                    continue;
                $payment->calcSums();
                $payment->saveToDatabase();
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function includeToPayment($leadsArray, $paymentId) {
        $tables = array(
            'webmasters_old_leads',
        );
        $ids = array();
        foreach ($leadsArray as $v) {
            if (!in_array($v->item_type, $tables))
                continue;
            if (!isset($ids[$v->item_type]))
                $ids[$v->item_type] = array();
            $ids[$v->item_type][] = $v->item_id;
        }

        try {

            $this->database->beginTransaction();

            $paymentsToRecalc = array($paymentId);

            foreach ($ids as $k => $v) {
                $quotedList = dbQuote($this->database, $v);
                $paymentsToRecalc = array_merge($paymentsToRecalc, $this->database->fetchCol("
            select distinct payment_id
            from {$k}
            where id in ($quotedList)
          "));
                $this->database->query("
            update {$k}
            set payment_id = ?
            where id in ($quotedList)
          ", array($paymentId));
            }

            $paymentsToRecalc = array_unique($paymentsToRecalc);

            foreach ($paymentsToRecalc as $id) {
                $payment = new T3Payment();
                if ($payment->fromDatabase($id) === false)
                    continue;
                $payment->calcSums();
                $payment->saveToDatabase();
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function getLinksToWireWebmasters() {

        if (T3Users::getInstance()->getCurrentUser()->isGuest)
            return;

        $data = $this->database->fetchAll("
      select a.webmaster_id, b.systemName, a.wire_data
      from webmasters_payments_systems a
      left join users_company_webmaster b
      on a.webmaster_id = b.id
      where FIND_IN_SET(a.systems_used, 'wire')>0
    ");


        $result = array();
        foreach ($data as $v) {
            $wireData = unserialize($v['wire_data']);
            $result[] = array(
                'link' => "<a href='/en/account/payments/webmasters-payment-settings?webmaster_id={$v['webmaster_id']}'>{$v['systemName']} ({$v['webmaster_id']})</a>",
                'comment' => empty($wireData['comment']) ? '' : $wireData['comment'],
            );
        }

        return $result;
    }

    public function getMaturedPaymentsWebmasters() {
        $s1 = "";
        $s2 = "";
        foreach (T3Payments::$partsData as $v1) {
            foreach ($v1['tables'] as $table) {
                $s1 = $s2 . " select payment_id, webmaster_id, sum(action_sum) as action_sum from `{$table}` where payment_id is null group by webmaster_id ";
                $s2 = $s1 . " union all ";
            }
        }
        
        // самая низкая минималка по всем системам
        $min = self::getMinimumOfAllSystems();

        $ar = T3Db::apiReplicant()->fetchAll(
            "select " . 
                "tmp3.webmaster_id, " . 
                "tmp3.action_count, " . 
                "tmp3.action_sum, " . 
                "tmp2.creation_datetime, " . 
                "tmp2.period_formal_end, " . 
                "tmp2.period_real_ends, " . 
                "ucw.payPeriod, " . 
                "ucw.reg_date " .
            "from " . 
                "(select " . 
                    "tmp1.webmaster_id, " . 
                    "count(*) as action_count, " . 
                    "sum(tmp1.action_sum) as action_sum " . 
                "from " .
                    "({$s1}) as tmp1 " .
                "group by tmp1.webmaster_id) as tmp3 " .
            "left join ( " .
                "select " . 
                    "webmaster_id, " . 
                    "creation_datetime, " . 
                    "period_formal_end, " . 
                    "period_real_ends " . 
                "from webmasters_payments " . 
                "group by webmaster_id " . 
                "having creation_datetime = max(creation_datetime) " .
            ") as tmp2 on tmp3.webmaster_id = tmp2.webmaster_id " .
            "left join users_company_webmaster ucw on tmp3.webmaster_id = ucw.id " .
            "where " .
                "not (ucw.status in ('lock','hold','noappr','temp')) " .
                "and ucw.payPeriod != 'manual_only' " .
                "and tmp3.action_sum >= {$min} " .
                "and ucw.status = 'activ' " . 
            "order by ucw.systemName"
        );   

        $output = array();

        
        if(count($ar)){ 
            // групповая загрузка настроек по всем вебмастрам
            $webmasters = array();  
            foreach($ar as $v){
                $webmasters[] = $v['webmaster_id'];     
            }
            
            $this->loadSystems($webmasters);
            
            foreach ($ar as $v) {
                if ($v['action_sum'] < $this->getSystems($v['webmaster_id'])->getMinimum()) {
                    continue;
                }
                
                if ($v['period_formal_end'] === null){
                    $v['period_formal_end'] = $v['reg_date'];
                }
                
                $output[] = $v['webmaster_id'];
            } 
        }

        return $output;
    }

    public function getNextSuccessiveIdForWebmaster($webmasterId, $once = true) {

        if ($once) {  
            $r = $this->database->fetchOne('select max(successive_id) from webmasters_payments where webmaster_id=?', array($webmasterId));
            if (empty($r))
                return 1;
            return $r + 1;
        }
        else {  
            if ($this->nextSuccessiveIds === null)
                $this->nextSuccessiveIds = $this->database->fetchPairs('select webmaster_id, ifnull(max(successive_id), 0) from webmasters_payments group by webmaster_id');

            if (!isset($this->nextSuccessiveIds[$webmasterId]))
                return 1;
                
            return $this->nextSuccessiveIds[$webmasterId] + 1;
        }
    }

    public function getWebmastersSystemNames($lazy = true) {
        if ($lazy && $this->webmastersSystemNames !== null)
            return $this->webmastersSystemNames;
        $ar = $this->database->fetchAll('select id, systemName from users_company_webmaster');
        $this->webmastersSystemNames = array();
        foreach ($ar as $v)
            $this->webmastersSystemNames[$v['id']] = $v['systemName'];
        return $this->webmastersSystemNames;
    }

    public function initWebmastersPaymentsSystems($ids) {   
        if ($ids === $this->lastWebmastersPaymentsSystemsIds)
            return;

        if (!empty($ids)) {
            $list = dbQuote($this->database, $ids);
            $systems = $this->database->fetchAll("SELECT * FROM webmasters_payments_systems WHERE webmaster_id IN ({$list})");
        }
        else {
            $systems = array();
        }

        $this->lastWebmastersPaymentsSystemsResult = groupBy($systems, null, 'webmaster_id');
        $this->lastWebmastersPaymentsSystemsIds = $ids;
    }

    public function getWebmastersPaymentsSystems($id) {  
        if (!is_array($this->lastWebmastersPaymentsSystemsIds) || !in_array($id, $this->lastWebmastersPaymentsSystemsIds))
            $this->initWebmastersPaymentsSystems(array($id));

        if (!array_key_exists($id, $this->lastWebmastersPaymentsSystemsResult))
            return false;

        return $this->lastWebmastersPaymentsSystemsResult[$id];
    }

    public function initWebmastersPaymentsPeriods($ids) {     
        if ($ids === $this->lastWebmastersPaymentsPeriodsIds)
            return;

        if (!empty($ids)) {
            $list = dbQuote($this->database, $ids);
            $ar = $this->database->fetchAll("SELECT id, payPeriod FROM users_company_webmaster WHERE id IN ({$list})");
            $this->lastWebmastersPaymentsPeriodsResult = groupBy($ar, null, 'id', false, true);
        }
        else {
            $this->lastWebmastersPaymentsPeriodsResult = array();
        }

        $this->lastWebmastersPaymentsPeriodsIds = $ids;
    }

    public function getOneWebmasterPaymentsPeriod($id) {  
        return $this->database->fetchOne('select payPeriod from users_company_webmaster where id = ?', array($id));
    }

    public function getWebmastersPaymentsPeriod($id) {
        if (!in_array($id, $this->lastWebmastersPaymentsPeriodsIds)){
            $this->initWebmastersPaymentsPeriods(array($id));
        }
        return $this->lastWebmastersPaymentsPeriodsResult[$id];
    }

    public function readMaturedPaymentsFromCache() {
        if ($this->maturedPaymentsCache !== null){
            return $this->maturedPaymentsCache;
        }
            
        $now = mySqlDateFormat();
        $ar = $this->database->fetchAll("SELECT * FROM webmasters_payments_cache WHERE cache_date = ?", array($now));
        
        if (count($ar) == 0)
            return false;
            
        $this->maturedPaymentsCache = array();
        
        foreach ($ar as $k => $v) {
            $v['id'] = 0;
            $object = new T3Payment();
            $object->fromArray($v);
            $object->actualActions = unserialize($v['actual_actions']);
            $this->maturedPaymentsCache[$k] = $object;
        }
        return $this->maturedPaymentsCache;
    }

    public function needToTakeFeeFrom($webmasterId) {   
        if ($this->needToTakeFeeFromResult === null) {
            $this->needToTakeFeeFromResult = groupColumnBy($this->database->fetchAll('SELECT fee, webmaster_id FROM webmasters_payments_holds'), 'webmaster_id');
        }

        if (array_key_exists($webmasterId, $this->needToTakeFeeFromResult)){
            return $this->needToTakeFeeFromResult[$webmasterId];     
        }
        else {
            return $this->needToTakeFeeFromResult[0];
        }
    }

    public function unfreezeWebmastersPaymentsCacheProcess() {
        T3System::setValue("webmasters_payments_cache_process", 0);
    }

    public static function requestApprovement($webmaster_id, $reasonObject) {
        $request = new T3Payments_ApprovementRequest();
        
        $request->record_datetime   = mySqlDateTimeFormat();
        $request->webmaster_id      = $webmaster_id;
        
        if (!is_array($reasonObject)){
            $reasonObject = array('text' => $reasonObject);
        }
        
        $request->reason_object         = $reasonObject;
        $request->reason_object_text    = $reasonObject['text'];
        
        $request->insertIntoDatabase();
        
        return $request;
    }

    public function makeMaturedPaymentsCache(&$array) {
        try {

            if (T3System::getValue("webmasters_payments_cache_process", 0)) {
                return;
            }

            T3System::setValue("webmasters_payments_cache_process", 1);

            if (empty($array)) {
                $this->maturedPaymentsCache = array();
                return;
            }
            $input = array();
            $now = mySqlDateFormat();
            foreach ($array as $k => $v) {
                $input[$k] = $v->toArray();
                unset($input[$k]['id']);
                $input[$k]['cache_date'] = $now;
                $input[$k]['actual_actions'] = serialize($v->actualActions);
            }
            
            $keys = array_keys(reset($input));
            $this->clearMatuerdPaymentsCache();

            $n = count($input);
            $n2 = 25;
            for ($i = 0; $i < $n; $i+=$n2) {
                $data = array_slice($input, $i, $n2);
                insertMultiple($this->database, 'webmasters_payments_cache', $keys, array_slice($input, $i, $n2));
            }
            $this->maturedPaymentsCache = $array;

            T3System::setValue("webmasters_payments_cache_datetime", mySqlDateTimeFormat());
            T3System::setValue("webmasters_payments_cache_available", 1);


            T3System::setValue("webmasters_payments_cache_process", 0);
        } catch (Exception $e) {

            T3System::setValue("webmasters_payments_cache_process", 0);
            T3System::setValue("webmasters_payments_cache_exception", $e->getCode() . " " . $e->getMessage());
        }
    }

    public function clearMatuerdPaymentsCache() {
        $this->database->delete('webmasters_payments_cache');
        T3System::setValue("webmasters_payments_cache_available", 0);
        $this->maturedPaymentsCache = null;
    }

    public function getMaturedPaymentsCacheDatetime() {
        $available = T3System::getValue("webmasters_payments_cache_available");
        $datetime = T3System::getValue("webmasters_payments_cache_datetime");
        if (!$available || empty($datetime)) {
            return false;
        } else {
            return $datetime;
        }
    }

    public function getPayInfoForT3LeadsV1($system, $webmasterId) {
        switch ($system) {
            case T3Payments::SYS_CHECK:
            case T3Payments::SYS_EPASS:
            case T3Payments::SYS_FETHARD:
            case T3Payments::SYS_PAYPAL:
            case T3Payments::SYS_WEBMONEY:
                return $system . " - " . $this->getSystems($webmasterId)->data[$system]['data'];
            case T3Payments::SYS_WIRE:
                return "WIRE - " . ifset($this->getSystems($webmasterId)->data[$system]['comment']);
        }
        return '';
    }

    public function getMaturedPayments() { 
        ini_set("memory_limit", "8048M");

        $cache = $this->readMaturedPaymentsFromCache();
        if ($cache !== false) {
            return $cache;
        }

        $ids = $this->getMaturedPaymentsWebmasters(); 
        
        // загрузка сразу всех вебмастреров
        T3WebmasterCompanys::loadCompanys($ids);
        T3Payments::loadHolds($ids);

        $this->initWebmastersPaymentsPeriods($ids);
        $this->initWebmastersPaymentsSystems($ids);

        $result = array();

        foreach ($ids as $webmasterId) {                                                                  
            $init_limits = array();
            $webmasters_payments = array();  
                 
            for ($resultI = null, $i = 0; ($resultI === null || $resultI >= 0) && $i < 2; $resultI--, $i++) {
                $payment = new T3Payment();

                $resultI = $payment->initializeByPreviousPayment(
                    $webmasterId, 
                    $this->getWebmastersPaymentsPeriod($webmasterId), 
                    $resultI
                );
                
                if ($resultI === false){
                    break;
                }
                
                $result[]               = $payment;                      
                $init_limits[]          = $resultI;
                $webmasters_payments[]  = $payment;     
            }  
        }
                                      
        $dates = array();
        $n = 0;
        
        foreach ($result as $payment) {
            if (!isset($dates[$payment->period_formal_end])) {
                $dates[$payment->period_formal_end] = $n;
                $n++;
            }
        }
        
        foreach ($result as $payment) {
            $payment->init_limit = $dates[$payment->period_formal_end];
        }   

        $this->makeMaturedPaymentsCache($result);

        return $result;
    }
    
    public function paymentsListHasBeenCreated($period, $formalPeriodEnd) {
        return $this->database->fetchOne(
            'select count(*)>0 from webmasters_payments where period_type = ? and date(period_formal_end) = date(?)', 
            array($period, $formalPeriodEnd)
        );
    }

    /*********************************************************************************************************/
    
    static protected $cacheLastPaySettinsMD5;
    
    public function getPaymentSystemsChanges($webmasterId) {
        // получаем кеш последних настроек по системам
        if(is_null(self::$cacheLastPaySettinsMD5)){
            $temp = T3Db::api()->fetchAll(
                "select * from (select webmaster_id, pay_system, md5(current_system_data) as `md5` from webmasters_payments_pays order by id desc) tmp " .
                "group by webmaster_id, pay_system"
            );
            
            self::$cacheLastPaySettinsMD5 = array();
            if(count($temp)){
                foreach($temp as $el){
                    if(!isset(self::$cacheLastPaySettinsMD5[$el['webmaster_id']])) self::$cacheLastPaySettinsMD5[$el['webmaster_id']] = array();
                    self::$cacheLastPaySettinsMD5[$el['webmaster_id']][$el['pay_system']] = $el['md5'];     
                }
            }
        }
        
        $nowPaySystems = T3Payments::getSystems($webmasterId);
        
        $result = array();

        foreach (T3Payments::getInstance()->getAvailableSystems() as $system) {
            $result[$system] = false;
            
            if(
                isset(self::$cacheLastPaySettinsMD5[$webmasterId][$system]) &&                                         // если есть предыдущая настройка
                md5(serialize($nowPaySystems->data[$system])) != self::$cacheLastPaySettinsMD5[$webmasterId][$system]  // и она не похожа на текщую
            ){
                $result[$system] = true;         
            } 
        }

        return $result;
    }
    
    /*********************************************************************************************************/

    public function reassignPaymentChanges($paymentsDate) {


        $data = $this->database->fetchAll('
      select id, webmaster_id, pay_systems_data from webmasters_payments
      where date(creation_datetime) >= date(?)
      order by webmaster_id
    ', array($paymentsDate));

        foreach ($data as $v) {

            $changes = $this->getPaymentSystemsChanges($v['webmaster_id']);

            $this->database->query('
        update webmasters_payments set
        payment_systems_changes = ?
        where id = ?
      ', array(serialize($changes), $v['id']));

            foreach ($changes as $system => $changed)
                if ($changed) {
                    $systems = new T3Payments_Systems();
                    $systems->fromDatabase($v['webmaster_id']);
                    $conv1 = T3Payments_Systems::getConvolution($systems->data[$system]);

                    $lastPaySystemArSer = $this->database->fetchOne('
            select current_system_data
            from webmasters_payments_pays
            where webmaster_id = ? and pay_system = ?
            order by record_datetime desc
            limit 1
          ', array($v['webmaster_id'], $system));

                    if ($lastPaySystemArSer === false) {
                        $conv2 = "FALSE";
                    } else {
                        $lastPaySystemAr = unserialize($lastPaySystemArSer);
                        $conv2 = T3Payments_Systems::getConvolution($lastPaySystemAr);
                        if (empty($conv2)) {
                            
                        }
                    }

                    echo "webmaster {$v['webmaster_id']}, system {$system} <br> $conv1 <br>$conv2<hr>";
                }
        }
    }

    public function fillCurrentSystemData() {
        $ar = $this->database->fetchAll('select * from webmasters_payments_pays');
        foreach ($ar as $v) {

            $paySystemsData = unserialize($v['pay_systems_data']);

            $this->database->query('
        update webmasters_payments_pays set current_system_data = ? where id = ?
      ', array($paySystemsData["{$v['pay_system']}_data"], $v['id']));
        }
    }

    public function & getLeadsBySearchQuery($query) {

        if ($query['empty']) {
            $result = array();
            return $result;
        }

        $fields = array('id', 'webmaster_id', 'payment_id', 'action_sum', 'action_datetime');

        $selects = array();

        $tables = array(
            'webmasters_old_leads',
        );

        foreach ($query['search_in'] as $table) {

            if (!in_array($table, $tables))
                continue;

            $select = $this->database->select()
            ->from($table, $fields + array('item_type' => "('$table')"))
            ->joinLeft('users_company_webmaster', "$table.webmaster_id=users_company_webmaster.id", array('webmaster_system_name' => 'systemName'));

            $webmasterId = null;

            if ($query['select_type'] == 'certain_payment') {
                if (!$query['also_free_leads']) {
                    $select->where("$table.payment_id = ?", $query['certain_payment_id']);
                } else {

                    if (empty($webmasterId))
                        $webmasterId = (int) ($this->database->fetchOne('
              select webmaster_id from webmasters_old_leads where payment_id = ? limit 1
              union
              select webmaster_id from webmasters_leads_sellings where payment_id = ? limit 1
            ', array($query['certain_payment_id'], $query['certain_payment_id'])));

                    $select->where("($table.payment_id = ? or (($table.payment_id is NULL or $table.payment_id = 0) and $table.webmaster_id=$webmasterId))", $query['certain_payment_id']);
                }
            }else if ($query['select_type'] == 'certain_webmaster') {
                $select->where("$table.webmaster_id = ?", $query['certain_webmaster_id']);
                if ($query['only_free_leads']) {
                    $select->where("($table.payment_id is NULL or $table.payment_id = 0)");
                }
            }


            $select->where('date(action_datetime) >= date(?)', $query['start_date']);
            $select->where('date(action_datetime) <= date(?)', $query['end_date']);

            $selects[] = $select;
        }

        $actualSelect = $this->database->select(array('*'))->union($selects);

        $result = $this->database->query($actualSelect)->fetchAll();
        return $result;
    }

    public function getMaturedPaymentFromCache($wId, $initLimit, $cache = null) {
        if ($cache === null)
            $cache = $this->readMaturedPaymentsFromCache();
        foreach ($cache as $payment)
            if ($payment->webmaster_id == $wId && $payment->init_limit == $initLimit)
                return $payment;
        return false;
    }

    public function systemsAreConfiguredFor($webmasterId) {

        $a = $this->database->fetchOne('
      select configured from
      webmasters_payments_systems
      where webmaster_id = ?
    ', array($webmasterId));

        return !empty($a);
    }

    public function makeMaturedPayments($ids, $initLimit) {

        if (T3System::getValue("makeMaturedPaymentsProcess", 0)) {
            return;
        }
        T3System::setValue("makeMaturedPaymentsProcess", 1);

        try {

            $cache = $this->readMaturedPaymentsFromCache();
            if ($cache === false) {
                $this->getMaturedPayments();
                $cache = $this->readMaturedPaymentsFromCache();
            }
            $this->clearMatuerdPaymentsCache(); // сразу же побыстрее очищаем этот е*аный кэш
            //$this->initWebmastersPaymentsPeriods($ids);
            //$this->initWebmastersPaymentsSystems($ids);

            foreach ($ids as $wId) {
                $payment = $this->getMaturedPaymentFromCache($wId, $initLimit, $cache);
                /* $payment = new T3Payment();
                  $payment->initializeByPreviousPayment($wId, $this->getWebmastersPaymentsPeriod($wId),$initLimit); */
                if ($payment === false)
                    continue;
                $payment->make();
            }

            T3System::setValue("makeMaturedPaymentsProcess", 0);
        } catch (Exception $e) {

            T3System::setValue("makeMaturedPaymentsProcess", 0);
        }
    }

    /*
      public function getMaturedPaymentsPeriods($autoInclude){

      $allMinimum = $this->getMinimumOfAllSystems();

      $result = array();

      foreach(T3Payments::$periodTypesData as $key => $value){

      if($key === $autoInclude){
      $result[] = $key;
      continue;
      }

      $ids = $this->getMaturedPaymentsWebmasters($key);

      $list = dbQuote($this->database, $ids);
      if(!empty($ids)){
      $systems = $this->database->fetchAll("
      SELECT *
      FROM webmasters_payments_systems
      WHERE webmaster_id IN ($list)
      ");
      $systems = groupBy($systems, null, 'webmaster_id');
      }else
      $systems = array();

      $b = false;

      foreach($ids as $webmasterId){
      $payment = new T3Payment();
      if($payment->initializeByPreviousPayment($webmasterId)===false)
      continue;
      if(isset($systems[$webmasterId])){
      $systemsObj = new T3Payments_Systems();
      $systemsObj->fromArray($systems[$webmasterId]);
      $minimum = $systemsObj->getMinimum();
      }else{
      $minimum = $allMinimum;
      }
      if($payment->total_value<$minimum)
      continue;
      $b = true;
      break;
      }

      if($b){
      $result[] = $key;
      }

      }

      return $result;

      }
     */

    /**
     * Получение массива настроек вебмастера
     *
     * @return array
     */
    public function getHolds_Array() {

        $result = $this->database->fetchAll("
          select
          a.webmaster_id, c.systemName, c.payPeriod as pay_period, ifnull(b.fee, '1') as fee,
          ifnull(b.holds_used, 'a:0:{}') as holds_used, ifnull(b.holds,  'a:0:{}') as holds from (
          select id as webmaster_id from users_company_webmaster where payPeriod != 'twicemonthly'
          union
          select webmaster_id from webmasters_payments_holds) a
          left join webmasters_payments_holds b
          on a.webmaster_id = b.webmaster_id
          left join users_company_webmaster c
          on a.webmaster_id = c.id
        ");

        return $result;


        $result = $this->database->fetchAll('
            SELECT webmasters_payments_holds.*, users_company_webmaster.systemName
            FROM webmasters_payments_holds
            INNER JOIN users_company_webmaster
            ON (webmasters_payments_holds.webmaster_id = users_company_webmaster.id)
        ');

        $uniquePayPeriod = $this->database->fetchPairs("select id ,systemName from users_company_webmaster where users_company_webmaster.payPeriod != ?", self::PERIOD_TYPE_TWICEMONTHLY);

        $resultHeader = array();
        foreach ($result as $res) {
            $resultHeader[$res['webmaster_id']] = true;
        }

        foreach ($uniquePayPeriod as $id => $systemName) {
            if (!isset($resultHeader[$id])) {
                $result[] = array(
                    'webmaster_id' => $id,
                    'fee' => '1',
                    'holds_used' => 'a:0:{}',
                    'holds' => 'a:0:{}',
                    'systemName' => $systemName,
                );
            }
        }

        $tableIDs = array();
        foreach ($result as $res) {
            $tableIDs[] = $res['webmaster_id'];
        }

        if (count($tableIDs)) {
            $payPeriods = $this->database->fetchPairs("select id ,payPeriod from users_company_webmaster where id in (" . implode(",", $tableIDs) . ")");


            foreach ($result as &$res) {
                $res['pay_period'] = $payPeriods[$res['webmaster_id']];
            }
        }

        return $result;
    }

    public function getNotPaidWebmastersPaymentData($webmasterId) {

        $data = $this->database->fetchRow("
        select * from webmasters_payments
        where webmaster_id = ? and !fully_paid
      ", array($webmasterId));

        if (empty($data))
            return false;

        $period = $this->database->fetchOne('select payPeriod from users_company_webmaster where id = ?', array($webmasterId));


        $zd = new Zend_Date($data['period_formal_end'], MYSQL_DATETIME_FORMAT_ZEND);
        //$zd->addDay(1);
        $nextPaymentDate = DateFormat::dateOnly($zd->toString(MYSQL_DATETIME_FORMAT_ZEND));
        $nextDateForAll = $this->getNextDateForAll($period, $data['period_formal_end']);
        if ($nextDateForAll !== false) {
            $afterNextPaymentDate = DateFormat::dateOnly($nextDateForAll->toString(MYSQL_DATETIME_FORMAT_ZEND));
        } else {
            $afterNextPaymentDate = "";
        }

        return array(
            'nextPaymentDate' => $nextPaymentDate,
            'afterNextPaymentDate' => $afterNextPaymentDate,
        );
    }

    static public function createSpecialListItemIsNotExist($webmasterID) {
        if (!self::getInstance()->database->fetchOne('select count(*) from webmasters_payments_holds where webmaster_id=?', $webmasterID)) {
            self::createSpecialListItem($webmasterID);
        }
    }

    static public function createSpecialListItem($webmasterID) {
        if ($webmasterID) {
            $wmObj = new T3Payments_Holds();
            $wmObj->fromDatabase($webmasterID);

            if (!$wmObj->id) {
                $obj = new T3Payments_Holds();
                $obj->id = $webmasterID;
                $obj->webmaster_id = $webmasterID;
                $obj->insertIntoDatabase();
                return $obj;
            }
        }
        return false;
    }

    /**
     * ??зменение параметра Fee
     *
     * @param mixed $webmasterID
     * @param mixed $fee
     */
    static public function changeFee($webmasterID, $fee) {
        $fee = (string) (int) (bool) $fee;
        $webmasterID = (int) $webmasterID;

        self::createSpecialListItemIsNotExist($webmasterID);

        self::getInstance()->database->update('webmasters_payments_holds', array(
            'fee' => $fee,
        ), "webmaster_id={$webmasterID}");
    }

    /**
     * Удаление всех уникальных настроек определенного вебмастера
     *
     * @param mixed $webmasterID
     */
    static function deleteHoldAndFeeSettings($webmasterID) {
        $webmasterID = (int) $webmasterID;
        self::getInstance()->database->delete('webmasters_payments_holds', "webmaster_id={$webmasterID}");
        self::getInstance()->database->update('users_company_webmaster', array('payPeriod' => self::PERIOD_TYPE_TWICEMONTHLY), "id=" . (int) $webmasterID);
    }

    /**
     * ??зменение одного значения холда
     *
     * @param mixed $webmasterID
     */
    static function changeOneHoldValue($webmasterID, $holdType, $value) {
        $webmasterID = (int) $webmasterID;

        self::createSpecialListItemIsNotExist($webmasterID);

        $value = (int) $value;
        $holds = new T3Payments_Holds();
        $holds->fromDatabase($webmasterID);
        $holds->useHold($holdType, true);
        $holds->holdsAr[$holdType] = $value;
        $holds->saveToDatabase();
    }

    /**
     * Удаление одного значения холда
     *
     * @param mixed $webmasterID
     */
    static function deleteOneHoldValue($webmasterID, $holdType) {
        $webmasterID = (int) $webmasterID;
        $holds = new T3Payments_Holds();
        $holds->fromDatabase($webmasterID);
        $holds->useHold($holdType, false);
        $holds->saveToDatabase();
    }

    static function changePayPeriod($webmasterID, $payPeriod) {
        $webmasterID = (int) $webmasterID;

        if (array_search($payPeriod, array(
            self::PERIOD_TYPE_WEEKLY,
            self::PERIOD_TYPE_BIWEEKLY,
            self::PERIOD_TYPE_TWICEMONTHLY,
            self::PERIOD_TYPE_MONTHLY,
            self::PERIOD_TYPE_MANUAL_ONLY
        )) !== false) {
            $company = new T3WebmasterCompany();
            $company->fromDatabase($webmasterID);

            if ($company->id) {
                $company->payPeriod = $payPeriod;
                $company->saveToDatabase();
                return true;
            }
        }

        return false;
    }

    public function setTypeSettingValue($type, $param, $value) {
        return $this->database->update('payment_types', array($param => $value), 'pay_type=' . "'" . $type . "'");
    }

    public function generateTestWebmaster() {

        if (T3System::getValue('test_webmaster_generated')) {
            $this->deleteTestWebmaster();
        }

        try {

            $this->database->beginTransaction();

            $b = new T3WebmasterCompany();
            $b->fromArray(array(
                'id' => null,
                'systemName' => 'test_webmaster',
                'companyName' => 'Test Webmaster',
                'points' => '0',
                'payPeriod' => 'weekly',
                'refaffid' => '0',
                'autoSubAccounts' => '0',
                'regProductsInterest' => '0',
                'regComments' => '',
                'defaultProcCallVerify' => '0',
                'agentID' => '0',
                'status' => 'activ',
                'balance' => '100000.00',
                'reg_date' => '2010-04-01 03:21:42',
                'reg_website' => '',
                'howDidFindUs' => '',
                'T3LeadsVersion1_ID' => '999999',
                'T3LeadsVersion1_Login' => 'Test Webmaster V1',
            ));
            $b->insertIntoDatabase();


            $leadsSellingsIds = array();
            for ($i = 0; $i < 400; $i++) {
                $zd = new Zend_Date();
                $zd->subSecond(rand(10000, 600000));
                $this->database->insert('webmasters_old_leads', array(
                    'id' => null,
                    'webmaster_id' => $b->id,
                    'payment_id' => null,
                    'action_sum' => (rand(1, 5) * 10.0),
                    'action_datetime' => $zd->toString(MYSQL_DATETIME_FORMAT_ZEND),
                    'synh_session' => '0',
                    'synh_id' => '0',
                ));
                $leadsSellingsIds[] = $this->database->lastInsertId();
            }

            $this->database->insert(
            'webmasters_payments_holds', array(
                'webmaster_id' => $b->id,
                'fee' => '0',
                'holds_used' => 'a:4:{i:0;s:5:"leads";i:1;s:9:"movements";i:2;s:7:"bonuses";i:3;s:9:"old_leads";}',
                'holds' => 'a:4:{s:5:"leads";i:7;s:9:"movements";i:7;s:7:"bonuses";i:7;s:9:"old_leads";i:7;}',
            )
            );

            $this->database->insert(
            'webmasters_payments_systems', array(
                'webmaster_id' => $b->id,
                'systems_used' => 'wire',
                'configured' => '1',
                'check_part' => NULL,
                'check_data' => 'N;',
                'epass_part' => NULL,
                'epass_data' => 'N;',
                'paypal_part' => NULL,
                'paypal_data' => 'N;',
                'webmoney_part' => NULL,
                'webmoney_data' => 'N;',
                'wire_part' => '1.00',
                'wire_data' => 'a:8:{s:15:"name_on_account";s:15:"Test Test, Inc.";s:9:"bank_name";s:18:"Washington Mutual ";s:14:"account_number";s:10:"3126510781";s:5:"swift";s:9:"329971627";s:12:"bank_address";s:1:"-";s:10:"bank_phone";s:1:"-";s:13:"owner_address";s:1:"-";s:11:"owner_phone";s:1:"-";}',
            )
            );

            T3System::setValue('test_webmaster_generated', 1);
            T3System::setValue('test_webmaster_data', array(
                'webmaster_id' => $b->id,
                'old_leads_ids' => $leadsSellingsIds,
            ));

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function agentServicesWebmaster($agentId, $webmasterId) {

        return $this->database->fetchOne(
        'select count(*) from users_company_webmaster where agentID = ? and id = ?', array($agentId, $webmasterId)) != '0';
    }

    public function getTestWebmasterData() {
        if (!T3System::getValue('test_webmaster_generated'))
            return array();

        return T3System::getValue('test_webmaster_data');
    }

    public function deleteTestWebmaster() {

        if (!T3System::getValue('test_webmaster_generated'))
            return;

        try {

            $data = T3System::getValue('test_webmaster_data');
            if (empty($data))
                return;

            $this->database->beginTransaction();

            $this->database->query('
          delete from users_company_webmaster
          where id = ?
        ', array($data['webmaster_id']));

            $this->database->query('
          delete from webmasters_old_leads
          where webmaster_id = ?
        ', array($data['webmaster_id']));

            $this->database->query('
          delete from webmasters_payments_holds
          where webmaster_id = ?
        ', array($data['webmaster_id']));

            $this->database->query('
          delete from webmasters_payments_systems
          where webmaster_id = ?
        ', array($data['webmaster_id']));

            $this->database->query('
          delete from webmasters_payments
          where webmaster_id = ?
        ', array($data['webmaster_id']));

            T3System::setValue('test_webmaster_generated', 0);

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function fillPaymentsSuccessiveIds() {

        $data = $this->database->fetchAll('select id , webmaster_id from webmasters_payments order by creation_datetime');

        $ids = array();
        foreach ($data as $v) {
            if (!isset($data[$v['webmaster_id']]))
                $data[$v['webmaster_id']] = 0;
            $data[$v['webmaster_id']]++;
            $this->database->update('webmasters_payments', array('successive_id' => $data[$v['webmaster_id']]), "id = {$v['id']}");
        }
    }

    public function fillPaysTable() {

        $payments = $this->database->fetchAll('select * from webmasters_payments');

        try {

            $this->database->beginTransaction();

            $this->database->delete('webmasters_payments_pays');

            foreach ($payments as $paymentAr) {

                $payment = new T3Payment();
                $payment->fromArray($paymentAr);

                $i = 1;

                foreach ($payment->payHistory as $v) {

                    $pay = new T3Payments_Pay();

                    $pay->payment_id = $payment->id;
                    $pay->webmaster_id = $payment->webmaster_id;
                    $pay->record_datetime = $v['datetime'];
                    $pay->user_id = '1000005';
                    $pay->user_ip_address = null;
                    $pay->successive_id = $i++;
                    $pay->pay_system = $v['pay_system'];
                    $pay->value = $payment->valuesBySystems[$v['pay_system']];
                    $pay->value_without_fee = $payment->valuesBySystemsWithoutFee[$v['pay_system']];
                    $pay->fee = $payment->feeBySystems[$v['pay_system']];

                    $pay->insertIntoDatabase();
                }
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function getHistoryArray($paymentId) {

        return $this->database->fetchAll('
        select * from
        webmasters_payments_pays
        where payment_id = ?
        order by record_datetime desc
      ', array($paymentId));
    }

    public function fillPaymentSystemsInPaymentsTable() {

        $data = $this->database->fetchAll('select * from webmasters_payments');

        foreach ($data as $v) {

            $p = new T3Payment();
            $p->fromArray($v);


            $paymentSystems = array();
            foreach ($p->valuesBySystems as $k => $v)
                if (!empty($v))
                    $paymentSystems[] = $k;

            $str = implode(', ', $paymentSystems);

            $this->database->query('update webmasters_payments set payment_systems = ? where id = ?', array($str, $p->id));
        }
    }

    public function thereIsPayForPayment($paymentId, $system) {

        return $this->database->fetchOne('
        select count(*)>0 from webmasters_payments_pays where payment_id = ? and pay_system = ?
      ', array(
            $paymentId, $system
        )) != 0;
    }

    public function getDataForWholePeriodPays($dateTimeFrom, $dateTimeTill, $webmasterId) {

        return $this->database->fetchAll('
        SELECT wpp.webmaster_id as webmaster_id, SUM(wpp.value) AS paid_value, ucw.systemName as webmaster_systemName, u.email as webmaster_email
        FROM webmasters_payments_pays wpp
        LEFT JOIN users_company_webmaster ucw
        ON wpp.webmaster_id = ucw.id
        LEFT JOIN users u
        ON wpp.webmaster_id = u.company_id
        WHERE
        date(record_datetime) >= date(?)
        && date(record_datetime) <= date(?)
        && (? || webmaster_id = ?)
        GROUP BY wpp.webmaster_id
        ORDER BY SUM(wpp.value) DESC
      ', array(
            $dateTimeFrom,
            $dateTimeTill,
            (int) (empty($webmasterId)),
            $webmasterId,
        ));
    }

    public function getWebmastersForSelectInput() {
        return $this->database->fetchPairs('select id, systemName from users_company_webmaster order by systemName');
    }

    /**
     * Функиця архивирования пачки законченных инвойсов
     * Перемещает детальные данные (лиды, мувменты, эддинги) завершенных 3 или более месяца назад инвосов ы архивную таблицу
     */
    static public function archive() {
        ini_set("memory_limit", "2048M");
        set_time_limit(600);

        if (!T3Db::api()->fetchOne("select id from webmasters_payments_arh_log where `status` = 'run'")) {

            $start = microtime(1);

            $log = array(
                'start' => date('Y-m-d H:i:s'),
                'end' => '',
                'runtime' => '',
                'memory' => '',
                'date' => '',
                'count' => '',
                'payments' => '',
                'leads' => '',
                'movements' => '',
                'reason' => '',
            );
            T3Db::api()->insert("webmasters_payments_arh_log", $log);
            $logID = T3Db::api()->lastInsertId();

            T3Db::api()->beginTransaction();
            try {
                // максимальное количесво обрабатываемых за 1 раз транзакций
                $log['count'] = 100;

                $log['date'] = date('Y-m-d', mktime(0, 0, 0, date('m') - 3, date('d'), date('Y')));

                $arhIds = T3Db::api()->fetchCol(
                "select id from webmasters_payments where fully_paid='1' and last_pay_datetime < ? and `archive` = 0 limit {$log['count']}", $log['date']
                );

                $log['payments'] = count($arhIds);

                if ($log['payments']) {
                    // Если есть инвойсы для архивирования   
                    $tables = array(
                        array('webmasters_clicks', 'webmasters_payments_arh_clicks', 'payment_id', 'clicks'),
                        array('webmasters_leads_movements', 'webmasters_payments_arh_movements', 'payment_id', 'movements'),
                        array('webmasters_leads_sellings', 'webmasters_payments_arh_leads', 'payment_id', 'leads'),
                        array('webmasters_referral_addings', 'webmasters_payments_arh_referral_addings', 'payment_id', 'addings'),
                    );

                    foreach ($tables as $tbl) {
                        $all = T3Db::api()->fetchAll("select * from `{$tbl[0]}` where `{$tbl[2]}` in (" . implode(",", $arhIds) . ")");

                        $log[$tbl[3]] = count($all);

                        if ($log[$tbl[3]]) {
                            // скопировать, удалить
                            T3Db::api()->insertMulty($tbl[1], array_keys($all[0]), $all);
                            T3Db::api()->delete($tbl[0], "`{$tbl[2]}` in (" . implode(",", $arhIds) . ")");
                        }
                    }

                    // пометить как заархивированный
                    T3Db::api()->update("webmasters_payments", array(
                        'archive' => '1'
                    ), "id in (" . implode(",", $arhIds) . ")");
                }

                $log['memory'] = memory_get_usage() / 1024 / 1024;
                $log['end'] = date('Y-m-d H:i:s');
                $log['runtime'] = microtime(1) - $start;
                $log['status'] = 'good';

                T3Db::api()->update("webmasters_payments_arh_log", $log, "id={$logID}");
                T3Db::api()->commit();
            } catch (Exception $e) {
                T3Db::api()->rollBack();

                $log['status'] = 'error';
                $log['reason'] = $e->getMessage() . " (" . $e->getLine() . ")\r\n" . $e->getTraceAsString();

                T3Db::api()->update("webmasters_payments_arh_log", $log, "id={$logID}");

                echo "Error: \r\n\r\n" . $log['reason'];
            }
        } else {
            echo "Process locked";
        }
    }

    static public function deletePayment($id, $comment){
        $payment = new T3Payment();
        $payment->fromDatabase($id);

        T3Db::api()->insert("webmasters_payments_delete_log", array(
            'user'          => T3Users::getCUser()->id,
            'ip'            => $_SERVER['REMOTE_ADDR'],
            'payment_id'    => $id,
            'total_value'   => $payment->total_value,
            'comment'       => $comment,
        ));

        T3Db::api()->query("UPDATE `webmasters_bonuses` SET `payment_id`=NULL WHERE `payment_id`={$id}");
        T3Db::api()->query("UPDATE `webmasters_clicks` SET `payment_id`=NULL WHERE `payment_id`={$id}");
        T3Db::api()->query("UPDATE `webmasters_leads_movements` SET `payment_id`=NULL WHERE `payment_id`={$id}");
        T3Db::api()->query("UPDATE `webmasters_leads_sellings` SET `payment_id`=NULL WHERE `payment_id`={$id}");
        T3Db::api()->query("UPDATE `webmasters_sora_leads` SET `payment_id`=NULL WHERE `payment_id`={$id}");
        T3Db::api()->query("DELETE FROM `webmasters_payments` WHERE id={$id}");
    }

}