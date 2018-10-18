<?php

TableDescription::addTable('buyers_filters_conditions', array('id', 'channel_id', 'type_name', 'affirmative', 'works', 'misc',));

abstract class T3BuyerFilter_Condition extends DbSerializable {

    const MIN_LOG_TIME_LENGTH_SECONDS = 0.1;

    const PRIORITY_SIMPLEST  = 1;
    const PRIORITY_GETS_BODY = 2;
    const PRIORITY_COMPLEX   = 3;

    const CHANGE_OFFER    = 'changeOffer';
    const FILTER_VIOLATED = 'filterViolated';

    public static $availableTypes = array('Aba',
                                         'BankAccountNumberPatterns',
                                         'ActiveMilitary',
                                         'Address',
                                         'BankAccountMinMonths',
                                         'BankAccountNumberLength',
                                         'BankAccountType',
                                         'BlackList',
                                         'Date',
                                         'DirectDeposit',
                                         'Employer',
                                         'EmployerMinMonths',
                                         'HomePhoneArea',
                                         'IncomeSource',
                                         'LastAddressMinMonths',
                                         'LoanAmount',
                                         'MinAge',
                                         'MonthlyIncome',
                                         'OneCompany',
                                         'PayFrequency',
                                         'PhonesEquality',
                                         'SoldInDay',
                                         'States',
                                         'Webmasters',
                                         'Zip',
                                         'ZipRadius',
                                         'DebtAmount',
                                         'Email',
                                         'HomePhone',
                                         'WebmastersServerPost',
                                         'BankName',
                                         'SecureSubID',
                                         'UKPayFrequency',
                                         'UKIncomeSource',
                                         'UKDirectDeposit',
                                         'UKDebitCardProvided',
                                         'UKMonthlyIncome',
                                         'SelfEmployed',
                                         'IpAddress',
                                         'UKOwnHome',
                                         'UKAddressLength',
                                         'UKJobLength',
                                         'UKBankAccountType',
                                         'Counties',
                                         'EmployerBankNameEquality',
                                         //'MaxFrequency',
                                        'MaxPerHour',
                                         //'ConditionalBlackList_EmailSsn',
                                        'AddressesEquality',
                                         'UKSelfEmployed',
                                         'UKPhonesEquality',
                                         'WaitChannel',
                                         'WebmasterChannels',
                                         'UKWebmasterChannels',
                                         'SortCode',
                                         'GrossMonthlyIncome',
                                         'FourSSNDigitsInEmail',
                                         'Town',
                                         'DoNotPresentList',
                                         'UKDoNotPresentList',
                                         'MaxAge',
                                         'ABANumberLength',
                                         'Postcode',
                                         'Formtype',
                                         'Mobile',
                                         'Jobtitle',
                                         'Carrier',
                                         'City',
                                         'Province',
                                         'CAIncomeSource',
                                         'CABankAccountType',
                                         'ZipMini',
                                         'Ausstates',
                                         'AUSIncomeSource',
                                         'BSB',
                                        'CallDupLeads',
                                        'CallDupCalls',
                                        'CallChannel',
                                        'IncludeFHALoans',
                                        'HomeValue',
                                        'CreditGrade',
                                        'LTV',
                                        'Conventional',
                                        //'PersonalLoanCreditGrade',
                                        'RefinanceLoanAmount',
                                        'PropertyType',
                                        'PropertyUse',
                                        'RateType',
                                        'MortgageLates',
                                        'AnnualVerifiableIncome',
                                        'FHABankForeclosure',
                                        'RURegionLive',
                                        'RURegionPropiska',
                                        'RUCar',
                                        'RUHomeStatus',
                                        'RUIncomeSource',
                                        'CallIncome',
                                        'OwnVehicleTitle',
                                        'Bankrupcy',
                                        'VehicleYear',
                                        'VehicleMileage',
                                        'RUMaxSrok',
                                        'RUMinSrok',
                                        'UKOptInEmail',
                                        'MinYear',
                                        'MaxMileage',
                                        'TrafficPercent',
                                        'RUFssp',
                                        'RuFactCity',
                                        'RuRegistrationCity',
                                        'AmountToIncomeLimit',
                                        'SolarRoofShade',
                                        'SolarHomeOwner',
                                        'SolarElectricityProvider',
                                        'SolarPowerBill',
                                        'CreditScore',
                                        'SolarTrustedForm',
                                        'AnnualRevenue',
                                        'MonthsInBusiness',
                                        'CompanyStructure',        
                                        'DeviceType',
                                        'TrafficPercentWebmaster',
                                        'BusinessLoanIndustry',
                                        'MonthlyCreditCardSales',
                                        'VehicleRegistrationNumber',
);

    const DefaultSeparator = ",";

    public $id;
    public $channel_id;
    public $type_name;
    public $affirmative = 1;
    public $works = 0;
    public $misc = '';

    public $filter;
    public $channel;

    public $report;

    public $lastVerifiedLead;

    public $lastChangeOffer;

    public function GetPriority() {
        return T3BuyerFilter_Condition::PRIORITY_SIMPLEST;
    }

    public static function CollapseSpaces($s) {
        return trim(preg_replace('/\s+/i', ' ', $s));
    }

    public function getChannel($lazy = true) {
        /*
        if($lazy && !is_null($this->channel))
        return $this->channel;

      $this->channel = T3BuyerChannel::createFromDatabase($this->channel_id);
      return $this->channel;
      */
        return T3BuyerChannels::getChannel($this->channel_id);
    }

    public function getChannelLeadsNumberThisDay() {
        return T3BuyerChannel_SoldTimeZoneCache::getSoldLeads($this->getChannel());
    }

    public static function PrintPriorities() {
        foreach (T3BuyerFilter_Condition::$availableTypes as $type) {
            // if($i++ > 9) die($type);
            $fullname = "T3BuyerFilter_Condition_$type";
            $cond     = new $fullname();
            $pr       = $cond->getPriority();
            echo "$type : ";
            if ($pr == 1) {
                echo "PRIORITY_SIMPLEST";
            } else if ($pr == 2) {
                echo "PRIORITY_GETS_BODY";
            } else if ($pr == 3) {
                echo "PRIORITY_COMPLEX";
            }
            echo "<br>";
        }
    }

    public function  __construct() {

        parent::__construct();
        $this->tables[] = 'buyers_filters_conditions';
        $this->report   = new Report();

    }

    public abstract function getTitle();

    public abstract function getTypeName();

    public function toArray($tables = null) {
        if (is_null($this->channel_id) && !is_null($this->filter)) $this->channel_id = $this->filter->channelId;
        $this->affirmative = (int)$this->affirmative;
        $this->works       = (int)$this->works;
        $this->type_name   = $this->getTypeName();

        return parent::toArray($tables);
    }

    public function fromArray(&$array) {
        parent::fromArray($array);
        $this->affirmative = (int)$this->affirmative;
        $this->works       = (int)$this->works;
    }

    static protected $filtersCache = array();

    public function acceptsLead(T3Lead $lead) {
        // if(!isset(self::$filtersCache[$lead->id])) self::$filtersCache[$lead->id] = array();

        $this->lastVerifiedLead = $lead;

        // Если результтат работы этого фильтра еще не записан в кеш, проверить его иначе взять из кеша
        //if(!isset(self::$filtersCache[$lead->id][$this->type_name])){
            $startTime = microtime(true);

            $this->report->clear();

            if (!$this->works) {
                $this->report->ok($this->getTypeName());
            }
            else {
                if ($this->acceptsLeadStraight($lead) == $this->affirmative) {
                    $this->report->ok($this->getTypeName());
                } else {
                    $this->report->error($this->getTypeName(), self::FILTER_VIOLATED);
                }

                $finishTime = microtime(true);

                $lengthSeconds = $finishTime - $startTime;
                if ($lengthSeconds > self::MIN_LOG_TIME_LENGTH_SECONDS) {

                    // Сохранение медленных фильтров
                    try {
                        $logItem                  = new T3BuyerFilter_TimeLogItem();
                        $logItem->record_datetime = mySqlDateTimeFormat();

                        $logItem->condition_id        = $this->id;
                        $logItem->condition_type_name = $this->type_name;
                        $logItem->buyer_channel_id    = $this->channel_id;
                        $logItem->time_length_seconds = $lengthSeconds;

                        $logItem->insertIntoDatabase();

                    }
                    catch (Exception $e) {

                    }
                }
            }

            //self::$filtersCache[$lead->id][$this->type_name] = $this->report;
        //}

        //return self::$filtersCache[$lead->id][$this->type_name]->isNoError();
        return $this->report;
    }

    protected function changeOffer($newValue) {
        $this->report->notice($this->getTypeName(), self::CHANGE_OFFER)->setData('newValue', $newValue);
        $this->lastChangeOffer = $newValue;
        if ($this->channel_id == 10251) {
            $this->database->insert('hardcodes_log', array("dt" => mySqlDateTimeFormat(), "channel_id" => $this->channel_id, "type_name" => $this->getTypeName(), "new_value" => $newValue,));
        }
    }

    protected abstract function acceptsLeadStraight(T3Lead $lead);

    public function getBuyerDateTime($mysqlTime = null) {
        $ch = $this->getChannel();
        if ($ch === false) return empty($mysqlTime) ? mySqlDateTimeFormat() : $mysqlTime;

        return TimeZoneTranslate::toTheir($ch->timezone, $mysqlTime);
    }

    public function getDeltaTimeZoneSeconds() {
        $nowZone   = date_default_timezone_get();
        $mktimeNow = mktime();
        date_default_timezone_set($this->getChannel()->timezone);
        $mktimeBuyer = mktime();
        date_default_timezone_set($nowZone);

        return $mktimeBuyer - $mktimeNow;
    }

    public static function createFromTypeName($typeName) {
        $class  = "T3BuyerFilter_Condition_$typeName";
        $object = new $class();

        return $object;
    }

    public static function createFromDatabase($conditions) {
        $array  = self::fromDatabaseStatic('buyers_filters_conditions', $conditions);
        $object = self::createFromArray($array);

        return $object;
    }

    public static function createFromArray(&$array) {
        $object = self::createFromTypeName($array['type_name']);
        $object->fromArray($array);

        return $object;
    }

    public function getLeadsAcceptedByChannel($channelId) {
        return T3BuyerChannel::getLeadsAcceptedByChannel($channelId);
    }

    public function getActualFieldName() {
        return false;
    }

    public function getActualValue(T3Lead $lead) {
        return false;
    }

    public function getTextReport() {
        /*
        $field = $this->getActualFieldName();
        if(empty($field)){
          $field = '_there_is_no_actual_field_';
          $value = '_there_is_no_actual_field_';
        }else{
          $value = $this->getActualValue($this->lastVerifiedLead);
        }
        //return "Filter: {$this->type_name}; Field: {$field}; Value: {$value};";
        return "{$this->type_name} ({$field}={$value})";
        */
        return $this->type_name;
    }

    /*protected function getPropertyOrMethod($lead, $name){

          if  (property_exists($lead, $name))
        return $lead->$name;

      elseif  (property_exists($lead->body, $name))
        return $lead->body->$name;

      elseif  (method_exists($lead, $name))
        return $this->callMethod($lead,$name);

      elseif  (method_exists($lead->body, $name))
        return $this->callMethod($lead->body,$name);

      elseif  (method_exists($this, $name))
        return $this->callMethod($this,$name);

      throw new Exception('T3BuyerFilter_Condition getPropertyOrMethod $name invalid');

    }*/

}

/*

Aba : PRIORITY_COMPLEX
BankAccountNumberPatterns : PRIORITY_COMPLEX
ActiveMilitary : PRIORITY_GETS_BODY
Address : PRIORITY_GETS_BODY
BankAccountMinMonths : PRIORITY_GETS_BODY
BankAccountNumberLength : PRIORITY_GETS_BODY
BankAccountType : PRIORITY_GETS_BODY
BlackList : PRIORITY_SIMPLEST
Date : PRIORITY_COMPLEX
DirectDeposit : PRIORITY_GETS_BODY
Employer : PRIORITY_COMPLEX
EmployerMinMonths : PRIORITY_GETS_BODY
HomePhoneArea : PRIORITY_COMPLEX
IncomeSource : PRIORITY_GETS_BODY
LastAddressMinMonths : PRIORITY_GETS_BODY
LoanAmount : PRIORITY_GETS_BODY
MinAge : PRIORITY_COMPLEX
MonthlyIncome : PRIORITY_GETS_BODY
OneCompany : PRIORITY_COMPLEX
PayFrequency : PRIORITY_GETS_BODY
PhonesEquality : PRIORITY_GETS_BODY
SoldInDay : PRIORITY_COMPLEX
States : PRIORITY_GETS_BODY
Webmasters : PRIORITY_GETS_BODY
Zip : PRIORITY_COMPLEX
DebtAmount : PRIORITY_GETS_BODY
Email : PRIORITY_COMPLEX
WebmastersServerPost : PRIORITY_COMPLEX
BankName : PRIORITY_COMPLEX
SecureSubID : PRIORITY_COMPLEX
UKPayFrequency : PRIORITY_GETS_BODY
UKIncomeSource : PRIORITY_GETS_BODY
UKDirectDeposit : PRIORITY_GETS_BODY
UKDebitCardProvided : PRIORITY_GETS_BODY
UKMonthlyIncome : PRIORITY_GETS_BODY
SelfEmployed : PRIORITY_GETS_BODY
IpAddress : PRIORITY_COMPLEX
UKOwnHome : PRIORITY_GETS_BODY
UKAddressLength : PRIORITY_GETS_BODY
UKJobLength : PRIORITY_GETS_BODY
UKBankAccountType : PRIORITY_GETS_BODY
Counties : PRIORITY_COMPLEX
EmployerBankNameEquality : PRIORITY_COMPLEX

 */
