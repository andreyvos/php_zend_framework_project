<?php

TableDescription::addTable('webmasters_payments', array(
    'id', 
    'webmaster_id', 
    'creation_datetime', 
    'period_formal_beg', 
    'period_formal_end',
    'period_real_begs', 
    'period_real_ends', 
    'period_type',
    'created_specially', 
    'actions_numbers', 
    'actions_values', 
    'holds',
    'total_value',
    'total_value_without_fee',
    'total_fee', 
    'pay_systems_data',
    'values_by_systems', 
    'values_by_systems_without_fee', 
    'fee_by_systems', 
    'paid_values', 
    'payment_systems',
    'fully_paid', 
    'pay_history', 
    'last_pay_datetime', 
    'init_limit', 
    'successive_id',
    'payment_systems_changes',
    'approvement_status',
    'approvement_request_type',
    'fraud_detector_request_id',
    'approvement_process_datetime',
    'approvement_process_user_id',
));

class T3Payment extends DbSerializable {

    public $id;
    public $webmaster_id;
    public $creation_datetime;
    public $period_formal_beg;
    public $period_formal_end;
    public $period_real_begs;
    public $period_real_ends;
    public $period_type;
    public $created_specially;
    public $actions_numbers;
    public $actions_values;
    public $holds;
    public $total_value;
    public $total_value_without_fee;
    public $total_fee;
    public $pay_systems_data;
    public $values_by_systems;
    public $values_by_systems_without_fee;
    public $fee_by_systems;
    public $paid_values;
    public $payment_systems;
    public $fully_paid;
    public $pay_history;
    public $last_pay_datetime;
    public $init_limit;
    public $successive_id;
    public $payment_systems_changes;
    public $approvement_status = T3Payments_ApprovementRequest::STATUS_not_requested;
    public $approvement_request_type;
    public $fraud_detector_request_id;
    public $approvement_process_datetime;
    public $approvement_process_user_id;
    public $webmaster;
    public $actualActions = array();
    public $realBegs = array();
    public $realEnds = array();
    public $actionsNumbers = array();
    public $actionsValues = array();
    public $paidValues = array();
    public $payHistory = array();
    public $valuesBySystems = array();
    public $valuesBySystemsWithoutFee = array();
    public $feeBySystems = array();
    public $paySystemsDataObj = array();
    public $paymentSystemsChanges = array();
    public $paymentSystems = array();
    protected $actualActionsGot = false;
    protected $actionsValuesGot = false;
    public $holdsAr;

    public function __construct() {

        parent::__construct();
        $this->tables = array('webmasters_payments');

        $this->realBegs         = T3Payment::readArray();
        $this->realEnds         = T3Payment::readArray();
        $this->actionsNumbers   = T3Payment::readArray();
        $this->actionsValues    = T3Payment::readArray();

        $this->paySystemsDataObj = new T3Payments_Systems();
        $this->paySystemsDataObj->fillDefault(-1);
    }

    public function toArray($tables = null) {

        $this->period_real_begs = serialize($this->realBegs);
        $this->period_real_ends = serialize($this->realEnds);
        $this->actions_numbers = serialize($this->actionsNumbers);
        $this->actions_values = serialize($this->actionsValues);
        $this->holds = serialize($this->holdsAr);
        $this->paid_values = serialize($this->paidValues);
        $this->pay_history = serialize($this->payHistory);
        $this->values_by_systems = serialize($this->valuesBySystems);
        $this->values_by_systems_without_fee = serialize($this->valuesBySystemsWithoutFee);
        $this->fee_by_systems = serialize($this->feeBySystems);
        $this->pay_systems_data = serialize($this->paySystemsDataObj->toArray());
        $this->payment_systems_changes = serialize($this->paymentSystemsChanges);
        $this->payment_systems = implode(', ', $this->paymentSystems);

        return parent::toArray($tables);
    }

    public function fromArray(&$array) {

        parent::fromArray($array);

        $this->realBegs = T3Payment::readArray($this->period_real_begs);
        $this->realEnds = T3Payment::readArray($this->period_real_ends);
        $this->actionsNumbers = T3Payment::readArray($this->actions_numbers);
        $this->actionsValues = T3Payment::readArray($this->actions_values);
        $this->holdsAr = T3Payment::readArray($this->holds);
        $this->paidValues = T3Payment::readArrayBySystems($this->paid_values);
        $this->payHistory = unserialize($this->pay_history);
        $this->valuesBySystems = T3Payment::readArrayBySystems($this->values_by_systems);
        $this->valuesBySystemsWithoutFee = T3Payment::readArrayBySystems($this->values_by_systems_without_fee);
        $this->feeBySystems = T3Payment::readArrayBySystems($this->fee_by_systems);
        $this->paySystemsDataObj = new T3Payments_Systems();
        $this->paySystemsDataObj->fromArray(unserialize($this->pay_systems_data));

        $this->paymentSystemsChanges = @unserialize($this->payment_systems_changes);
        if (empty($this->paymentSystemsChanges))
            $this->paymentSystemsChanges = array();



        $ar1 = explode(', ', $this->payment_systems);
        $systems = T3Payments::getSystemsTitles();
        $this->paymentSystems = array();
        foreach ($ar1 as $v) {
            if (!isset($systems[$v]))
                continue;
            $this->paymentSystems[] = $v;
        }
    }

    public function isDeletable() {
        foreach ($this->paidValues as $value)
            if ($value != 0)
                return false;
        return true;
    }

    public static function readArrayBySystems($string = null) {

        if (!empty($string)) {
            $result = @unserialize($string);
            if (empty($result))
                $result = array();
        }
        else {
            $result = array();
        }

        $ar = T3Payments::getInstance()->getAvailableSystems();

        foreach ($ar as $system){
            if (!array_key_exists($system, $result)){
                $result[$system] = 0;
            }
        }

        return $result;
    }

    public static function readArray($string = null) {
        if (!empty($string)) {
            $result = @unserialize($string);
            if (empty($result))
                $result = array();
        }
        else {
            $result = array();
        }

        foreach (T3Payments::$parts as $part){
            if (!array_key_exists($part, $result)){
                $result[$part] = null;
            }
        }

        return $result;
    }

    public function getWebmaster($lazy = true) {
        return T3WebmasterCompanys::getCompany($this->webmaster_id);
    }

    public function initializeByPreviousPayment($webmasterId, $periodType, $initLimit = null) {
        $core = T3Payments::getInstance();
        
        $lastPaymentRealEnds = $core->getLastPaymentRealEnds($webmasterId);

        if (empty($lastPaymentRealEnds)) {
            return false;
        }

        $this->webmaster_id = $webmasterId;

        $this->successive_id = T3Payments::getInstance()->getNextSuccessiveIdForWebmaster($webmasterId, false);

        if ($this->getWebmaster()->status != 'activ') {
            return false;
        }

        $this->creation_datetime = mySqlDateTimeFormat();
        $this->period_formal_beg = $lastPaymentRealEnds['period_formal_end'];
        unset($lastPaymentRealEnds['period_formal_end']);

        $zd = new Zend_Date($this->period_formal_beg, MYSQL_DATETIME_FORMAT_ZEND);
        $nowZd = new Zend_Date();
        
        if (!$zd->isEarlier($nowZd)) {
            return false;
        }

        $this->fully_paid = '0';
        $this->pay_datetime = null;

        $this->created_specially = 0;

        $holds = $core->getHolds($this->webmaster_id); 
        $this->holdsAr = array();
        foreach (T3Payments::$parts as $part)
            $this->holdsAr[$part] = $holds->getHold($part);

        sort($tmp2 = array_keys($lastPaymentRealEnds));
        sort($tmp3 = T3Payments::$parts);
        if ($tmp2 !== $tmp3) {
            //return false;
            // ошибка какая то
            // TODO
        }


        $this->paySystemsDataObj = T3Payments::getSystems($this->webmaster_id);
        $minimum = $this->paySystemsDataObj->getMinimum();  
        
        
        if ($this->paySystemsDataObj->configured && count($this->paySystemsDataObj->systems_used) != 0){
            $this->paymentSystemsChanges = T3Payments::getInstance()->getPaymentSystemsChanges($this->webmaster_id);    
        }
        else {
            return false; /// тут у вебмастера не настроены кошельки    
        }  

        $this->period_type = $periodType === null ? $this->getWebmaster()->payPeriod : $periodType;

        $dateBegZd = new Zend_Date();
        $dateEndZd = new Zend_Date();

        $limits = array();
        $formalEndZd = $core->getNextDateForAll($this->getWebmaster()->payPeriod, $this->period_formal_beg);
        $this->period_formal_end = $formalEndZd->toString(MYSQL_DATETIME_FORMAT_ZEND);
        $limits[] = $formalEndZd;

        while ($formalEndZd->isEarlier($nowZd)) {
            $formalEndZd = $core->getNextDateForAll($this->getWebmaster()->payPeriod, $this->period_formal_end);
            $this->period_formal_end = $formalEndZd->toString(MYSQL_DATETIME_FORMAT_ZEND);
            $aaaa[] = $this->period_formal_end;
            $limits[] = $formalEndZd;
        }

        if ($initLimit === null) {
            $initLimit = count($limits) - 1;
        }


        for ($limitI = $initLimit;; $limitI--) {

            if ($limitI < 0) {
                return false; ///// TODO
            }

            $formalEndZd = $limits[$limitI];

            $this->period_formal_end = $formalEndZd->toString(MYSQL_DATETIME_FORMAT_ZEND);

            $b1 = false;

            foreach ($lastPaymentRealEnds as $part => $time) { 
                //////////// Недавно добавили новый PART. По ходу, его $time === null, заполняем хоть чем-то ///////
                if (empty($time)) {  
                    foreach ($lastPaymentRealEnds as $someTime){
                        if (!empty($someTime)) {
                            $time = $someTime;
                            break;
                        }
                    }
                
                    if (empty($time)) {
                        return false;
                    }
                }
                ///////////////////////////////////////////////////////////

                $dateBegZd->set($time, MYSQL_DATETIME_FORMAT_ZEND);
                $dateBegZd->addSecond(1);
                $this->realBegs[$part] = $dateBegZd->toString(MYSQL_DATETIME_FORMAT_ZEND);

                $dateEndZd->set($formalEndZd);
                $dateEndZd->subDay($holds->getHold($part));
                $this->realEnds[$part] = $dateEndZd->toString(MYSQL_DATETIME_FORMAT_ZEND);

                if (!$dateBegZd->isEarlier($dateEndZd)) {
                    $formalEndZd = $core->getNextDateForAll($this->getWebmaster()->payPeriod, $this->period_formal_end);
                    $b1 = true;
                    break;
                    /*
                     * Здесь такая ситуация, что, во-первых, это первый пэймент для вебмастера, и, во-вторых, условие
                     * $this->total_value>=$minimum обязательно будет не соблюдено. Поэтому,
                     * не вычисляя дальше, делаем continue по внешнему циклу.
                     */
                }

                if (!$dateEndZd->isEarlier($nowZd)) {
                    /*
                     * Здесь мы получаем ситуацию, когда пытаемся создать пэймент слишком рано (какс cyberwire)
                     */
                    //return false;
                    //return $limitI;
                }
            }

            if ($b1) {
                continue;
            }

            $this->getActualActions(false);
            $this->getActualActionsValues(false);
            if ($this->getValuesBySystems() === false) {
                // Не настроены кошельки
                return false;
            }

            if ($this->total_value >= $minimum) {
                $this->init_limit = $limitI;
                return $limitI;
            } 
            else {
                return false;
            }
        }
    }

    public function rawInitialize(
        $webmasterId, 
        $periodType, 
        &$core, 
        &$lastPaymentRealEnds, 
        &$systemsAr, 
        &$minimum, 
        &$nowZd, 
        &$holds, 
        &$dateBegZd, 
        &$dateEndZd, 
        &$systemsAr
    ) {

        $dateBegZd = new Zend_Date();
        $dateEndZd = new Zend_Date();

        $core = T3Payments::getInstance();
        $lastPaymentRealEnds = $core->getLastPaymentRealEnds($webmasterId);

        if (empty($lastPaymentRealEnds)) {
            return false;
        }

        $this->webmaster_id = $webmasterId;
        if ($this->getWebmaster()->status != 'activ') {
            return false;
        }
        $this->creation_datetime = mySqlDateTimeFormat();
        $this->period_formal_beg = $lastPaymentRealEnds['period_formal_end'];
        unset($lastPaymentRealEnds['period_formal_end']);
        $zd = new Zend_Date($this->period_formal_beg, MYSQL_DATETIME_FORMAT_ZEND);
        $nowZd = new Zend_Date();
        //if(!$zd->isEarlier($nowZd))
        //  return false;
        $this->fully_paid = '0';
        $this->last_pay_datetime = null;

        $holds = $core->getHolds($this->webmaster_id);
        $this->holdsAr = array();
        foreach (T3Payments::$parts as $part)
            $this->holdsAr[$part] = $holds->getHold($part);

        /* sort($tmp2 = array_keys($lastPaymentRealEnds));
          sort($tmp3 = T3Payments::$parts);
          if($tmp2 !== $tmp3){
          return false;
          // ошибка какая то
          // TODO
          } */

        $this->paySystemsDataObj = new T3Payments_Systems();
        if ($systemsAr !== false) {
            $this->paySystemsDataObj = new T3Payments_Systems();
            $this->paySystemsDataObj->fromArray($systemsAr);
            $minimum = $this->paySystemsDataObj->getMinimum();
            $this->payment_systems_changes = T3Payments::getInstance()->getPaymentSystemsChanges($this->webmaster_id);
        } else {
            $minimum = $core->getMinimumOfAllSystems();
            $this->paySystemsDataObj->fillDefault($this->webmaster_id);
            return false; /// тут у вебмастера не настроены кошельки
        }

        $this->period_type = $periodType === null ? $this->getWebmaster()->payPeriod : $periodType;
    }

    public function initializeTillDate($webmasterId, $periodType, $specialDate) {

        $this->init_limit = null;

        $this->successive_id = T3Payments::getInstance()->getNextSuccessiveIdForWebmaster($webmasterId);

        $systemsAr = $this->database->fetchRow('SELECT * FROM webmasters_payments_systems WHERE webmaster_id = ?', array($webmasterId));

        if ($this->rawInitialize(
        $webmasterId, $periodType, $core, $lastPaymentRealEnds, $systemsAr, $minimum, $nowZd, $holds, $dateBegZd, $dateEndZd, $systemsAr
        ) === false)
            return false;


        $this->period_formal_end = $specialDate;
        $this->created_specially = 1;
        $formalEndZd = new Zend_Date();
        $formalEndZd->set($specialDate, MYSQL_DATETIME_FORMAT_ZEND);

        foreach ($lastPaymentRealEnds as $part => $time)
            $this->rawInitRealBegsEnds($part, $time, $dateBegZd, $dateEndZd, $formalEndZd, $nowZd, $holds);

        if ($this->rawPostInitialize($minimum, false) === false)
            return false;

        return true;
    }

    public function rawInitRealBegsEnds(&$part, &$time, &$dateBegZd, &$dateEndZd, &$formalEndZd, &$nowZd, &$holds) {


        $dateBegZd->set($time, MYSQL_DATETIME_FORMAT_ZEND);
        $dateBegZd->addSecond(1);
        $this->realBegs[$part] = $dateBegZd->toString(MYSQL_DATETIME_FORMAT_ZEND);

        $dateEndZd->set($formalEndZd);
        $dateEndZd->subDay($holds->getHold($part));
        $this->realEnds[$part] = $dateEndZd->toString(MYSQL_DATETIME_FORMAT_ZEND);


        if (!$dateEndZd->isEarlier($nowZd)) {
            /*
             * Здесь мы получаем ситуацию, когда пытаемся создать пэймент слишком рано (какс cyberwire)
             */
            //return false;
            //return $limitI;
        }

        return true;
    }

    public function calcSums() {

        $this->actualActions = array();
        $this->actionsNumbers = array();
        $this->actionsValues = array();
        $this->valuesBySystems = array();
        $this->valuesBySystemsWithoutFee = array();
        $this->feeBySystems = array();


        $this->getActualActions(false, true);
        $this->getActualActionsValues(false);
        if ($this->getValuesBySystems() === false) {
            // Не настроены кошельки
            return false;
        }

        return true;
    }

    public function rawPostInitialize($minimum, $verifyMinimum = true) {
        $this->getActualActions(false);
        $this->getActualActionsValues(false);
        if ($this->getValuesBySystems() === false) {
            // Не настроены кошельки
            return false;
        }
        if (!$verifyMinimum || $this->total_value >= $minimum) {
            return true;
        } else {
            return false;
        }
    }

    public function make() {
        try {
            $this->database->beginTransaction();
            $this->insertIntoDatabase();
            $this->updateRelatedTables();

            $this->initializeApprovementStatus();

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
            // записать в ошибки
        }
    }

    // must be within a transaction
    public function updateRelatedTables() {

        foreach (T3Payments::$parts as $part)
            foreach (T3Payments::$partsTables[$part] as $table) {
                if (empty($this->actualActions[$part][$table]))
                    continue;
                $list = dbQuote($this->database, $this->actualActions[$part][$table]);
                $this->database->query("
          UPDATE `$table`
          SET payment_id = ?
          WHERE id in ($list)
        ", array($this->id)); //->execute();
            }
    }

    public function getValuesBySystems() {
        $systems = T3Payments::getInstance()->getSystems($this->webmaster_id);
        $result = $systems->split($this->total_value, $withFee, $withoutFee, $fee);
        if ($result === false)
            return false;
        foreach ($withFee as $k => $v)
            $this->valuesBySystems[$k] = $v;

        $this->total_value_without_fee = 0.0;
        foreach ($withoutFee as $k => $v) {
            $this->valuesBySystemsWithoutFee[$k] = $v;
            $this->total_value_without_fee += $v;
        }

        $this->total_fee = 0.0;
        foreach ($fee as $k => $v) {
            $this->feeBySystems[$k] = $v;
            $this->total_fee += $v;
        }

        $this->paymentSystems = array();
        foreach ($this->valuesBySystems as $k => $v)
            if (!empty($v))
                $this->paymentSystems[] = $k;

        return $this->valuesBySystems;
    }

    protected function getActualActions($lazy = true, $recalc = false) {
        if ($lazy && $this->actualActionsGot){
            return $this->actualActions;
        }

        $queries = array();
        $values = array();

        foreach (T3Payments::$parts as $part)
            foreach (T3Payments::$partsTables[$part] as $table) {
                if (!$recalc) {
                    $queries[] = "(" . 
                        "SELECT '$part' as part_name, '$table' as table_name, id, payment_id " . 
                        "FROM `$table` " . 
                        "WHERE webmaster_id = ? and payment_id is null AND action_datetime <= ? " . 
                    ")";
                    $values[] = $this->webmaster_id;
                    //$values[] = $this->realBegs[$part];
                    $values[] = $this->realEnds[$part];
                } 
                else {
                    $queries[] = "(" .
                        "SELECT '$part' as part_name, '$table' as table_name, id, payment_id " .
                        "FROM `$table` " .
                        "WHERE payment_id = ? " .
                    ")";
                    $values[] = $this->id;
                }
            }

        if (empty($queries)){
            $ar = array();
        }
        else {
            $query = implode(' UNION ALL ', $queries);
            $ar = T3Db::apiReplicant()->fetchAll($query, $values);
        }

        $ar = groupBy($ar, array('part_name', 'table_name'));

        foreach (T3Payments::$parts as $part) {
            $this->actualActions[$part] = array();
            $this->actionsNumbers[$part] = 0;

            foreach (T3Payments::$partsTables[$part] as $table) {
                $this->actualActions[$part][$table] = array();
                if (!isset($ar[$part][$table]))
                    continue;
                foreach ($ar[$part][$table] as $v) {
                    if ($recalc || (!$recalc && empty($v['payment_id']))) {
                        $this->actionsNumbers[$part]++;
                        $this->actualActions[$part][$table][] = $v['id'];
                    } else {
                        // throw new Exception('Not Implemented');
                        // здесь какая то ошибка, нужно об это собщать. потому что все должны быть empty
                        // выяснилось, что не обязательно все empty . Если сначала была внеочередная выплата, то может быть payment_id не ноль
                    }
                }
            }
        }

        $this->actualActionsGot = true;
        return $this->actualActions;
    }

    public function remove() {
        try {
            $this->database->beginTransaction();
            $this->cleanRelatedTables();
            $this->deleteFromDatabase();
            $this->database->commit();
        } 
        catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
            // записать в ошибки
        }
    }

    public function cleanRelatedTables() {
        foreach (T3Payments::$parts as $part)
            foreach (T3Payments::$partsTables[$part] as $table) {
                //$list = dbQuote($this->database, $this->actualActions[$part][$table]);
                $this->database->query("UPDATE `$table` SET payment_id = NULL WHERE payment_id = ?", array($this->id)); //->execute();
            }
    }

    public function payToSystems($array, $sum = null) {

        if ($sum !== null) {
            $array[$array] = $sum;
        }

        $total = 0;
        foreach ($array as $system => $value) {
            $this->paidValues[$system] += $value;
            $total += $value;
        }

        $this->payHistory[] = array(
            'datetime' => mySqlDateTimeFormat(),
            'pay_data' => $array,
        );

        try { 
            $this->database->query('UPDATE users_company_webmaster SET balance = balance - ? WHERE id = ?', array($total, $this->webmaster_id)); //->execute();
            $this->saveToDatabase();
        } 
        catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
            // записать в ошибки
        }
    }

    public function getActionsValuesPart($part) {
        if (!isset($this->actionsValues[$part]))
            $this->actionsValues[$part] = 0;
        return $this->actionsValues[$part];
    }

    public function markPay($system) {

        if (T3Payments::getInstance()->thereIsPayForPayment($this->id, $system))
            return;

        if ($this->fully_paid) {
            return;
        }

        $sum = $this->valuesBySystems[$system];

        $this->paidValues[$system] = $sum;

        $this->payHistory[] = array(
            'datetime' => mySqlDateTimeFormat(),
            'pay_system' => $system,
            'pay_sum' => $sum,
        );


        $b = 1;
        foreach ($this->paidValues as $sys => $val)
            if ($val < $this->valuesBySystems[$sys]) {
                $b = 0;
                break;
            }
        $this->fully_paid = (int) $b;
        $this->last_pay_datetime = mySqlDateTimeFormat();

        try {
            $this->database->beginTransaction();
            $this->database->query('UPDATE users_company_webmaster SET balance = balance - ? WHERE id = ?', array($sum, $this->webmaster_id)); //->execute();
            $this->saveToDatabase();

            $oldLeadsValue =
            $this->valuesBySystems[$system] / $this->total_value * (
            $this->getActionsValuesPart(T3Payments::PART_BALANCE_V1) +
            $this->getActionsValuesPart(T3Payments::PART_MOVEMENTS_V1)
            );

            $needToTakeFee = (bool) (T3Payments::getInstance()->needToTakeFeeFrom($this->webmaster_id));
            $data = T3Payments::getInstance()->getSystemsData();
            
            if ($needToTakeFee)
                $oldLeadsFee = ($oldLeadsValue - $data[$system]['commision_const']) * ($data[$system]['commision_rate'] * 0.01) + $data[$system]['commision_const'];
            else
                $oldLeadsFee = 0.0;
            
            $oldLeadsValueWithoutFee = $oldLeadsValue - $oldLeadsFee;
            
            $pay = new T3Payments_Pay();

            $pay->payment_id = $this->id;
            $pay->webmaster_id = $this->webmaster_id;
            $pay->record_datetime = $this->last_pay_datetime;
            $pay->user_id = T3Users::getCUser()->id;
            $pay->user_ip_address = $_SERVER['REMOTE_ADDR'];
            $pay->successive_id = count($this->payHistory);
            $pay->pay_system = $system;
            $pay->value = $this->valuesBySystems[$system];
            $pay->value_without_fee = $this->valuesBySystemsWithoutFee[$system];
            $pay->fee = $this->feeBySystems[$system];

            $systemsAr = T3Payments::getInstance()->getWebmastersPaymentsSystems($this->webmaster_id);
            $paymensSystemsObject = new T3Payments_Systems();
            
            if ($systemsAr !== false)
                $paymensSystemsObject->fromArray($systemsAr);
            else
                $paymensSystemsObject->fillDefault($this->webmaster_id);

            $pay->pay_systems_data = serialize($paymensSystemsObject->toArray());
            $pay->current_system_data = serialize($paymensSystemsObject->data[$system]);
            $pay->insertIntoDatabase();


            T3Synh_V1User::createPayment(
                $this->id, 
                $this->webmaster_id, 
                $oldLeadsValueWithoutFee, 
                T3Payments::getInstance()->getPayInfoForT3LeadsV1(
                    $system, 
                    $this->webmaster_id
                ), 
                $oldLeadsFee, 
                $this->holdsAr[T3Payments::PART_BALANCE_V1], 
                mySqlDateFormat(strtotime($this->last_pay_datetime))
            );

            $this->sendEmail(true);


            $this->database->commit();
        } 
        catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
            // записать в ошибки
        }
    }  

    /**
    * Зачем эта функция??
    * 
    * @param mixed $lazy
    */
    protected function getActualActionsValues($lazy = true) {
        
        if ($lazy && $this->actionsValuesGot)
            return $this->actionsValues;

        $queries = array();

        $tmpI = 0;

        foreach (T3Payments::$parts as $part) {
            if (count(T3Payments::$partsTables[$part]) > 1) {
                $a = array();
                foreach (T3Payments::$partsTables[$part] as $table) {
                    if (empty($this->actualActions[$part][$table])) {
                        continue;
                    }
                    
                    $list = dbQuote($this->database, $this->actualActions[$part][$table]);
                    if (!empty($list)) {
                        $a[] = "(SELECT sum(action_sum) as action_sum FROM `$table` WHERE id IN ($list))";
                    }
                }
                
                $tmp = 'tmp' . $tmpI++;
                if (empty($a))
                    continue;
                    
                $a = implode(" UNION ALL ", $a);
                $queries[] = "(SELECT '$part' as part_name, sum(action_sum) as action_sum FROM ($a) as $tmp)";
            }
            elseif (count(T3Payments::$partsTables[$part]) == 1) {
                $table = reset(T3Payments::$partsTables[$part]);
                $list = dbQuote($this->database, $this->actualActions[$part][$table]);
                if (!empty($list)) {
                    $queries[] = "(SELECT '$part' as part_name, sum(action_sum) as action_sum FROM $table WHERE id IN ($list))";
                }
            }
        }

        if (empty($queries)){
            $ar = array();
        }
        else {
            $query = implode(' UNION ALL ', $queries);
            $ar = T3Db::apiReplicant()->fetchAll($query);
            $ar = groupBy($ar, null, 'part_name');
        }

        $this->total_value = 0;
        foreach (T3Payments::$parts as $part) {
            if (isset($ar[$part])){
                $this->actionsValues[$part] = $ar[$part]['action_sum'];
            }
            else {
                $this->actionsValues[$part] = 0;
            }
            $this->total_value += $this->actionsValues[$part];
        }

        return $this->actionsValues;
    }

    public function getPaymentSystemsValue($system) {
        if (!isset($this->valuesBySystemsWithoutFee[$system])){
            return 0.0;
        }

        return $this->valuesBySystemsWithoutFee[$system];
    }

    public function loadFromGetID($id) {
        $getID = new T3Payments_GetID($id, false);

        if ($getID->rolingParserGood) {
            $this->fromDatabase(array(
                'webmaster_id' => $getID->wm,
                'successive_id' => $getID->num,
            ));
            return true;
        }
        else {
            return false;
        }
    }

    public function getIdGet() {
        return sprintf("%03d.%d", $this->successive_id, $this->webmaster_id);
    }

    public function GetPeriodFormalEndMinusDay() {
        $zd = new Zend_Date($this->period_formal_end, MYSQL_DATETIME_FORMAT_ZEND);
        $zd->subDay(1);
        return $zd->toString(MYSQL_DATETIME_FORMAT_ZEND);
    }

    public function & getItemsGroupedForDocument() {

        $s1 = "";
        $s2 = "";

        $args = array();


        $partsSelects = array();
        $tablesCounter = 0;
        foreach (T3Payments::$partsData as $partName => $v1) {

            if ($partName == T3Payments::PART_CLICKS) {

                $partsSelects[] = "
                    select '$partName' as part_name, date(action_datetime) as actions_date, sum(clicks_count) as actions_count, sum(action_sum) as actions_amount
                    from webmasters_clicks tmp$tablesCounter where payment_id = ?
                    group by actions_date 
                ";
                $args[] = $this->id;
                $tablesCounter++;
            } 
            else {


                $tablesSelects = array();
                foreach ($v1['tables'] as $table) {  
                    $tablesSelects[] = " select action_datetime, action_sum from `$table` where payment_id = ? ";
                    $args[] = $this->id;
                }
                $tablesSelectsImploded = implode(' union all ', $tablesSelects);
                $tablesCounter++;

                $partsSelects[] = "
                    select '$partName' as part_name, date(action_datetime) as actions_date, count(*) as actions_count, sum(action_sum) as actions_amount
                    from ($tablesSelectsImploded) tmp$tablesCounter
                    group by actions_date 
                ";
            }
        }

        $partsSelectsImploded = implode(' union all ', $partsSelects);
        $partsSelectsImploded = "$partsSelectsImploded order by actions_date desc";

        $ar = $this->database->fetchAll($partsSelectsImploded, $args);

        if (empty($ar))
            return $ar;

        $result = array(
            'dates' => array(),
            'total' => array(
                'parts' => array(),
                'netAmount' => 0,
            ),
        );
        foreach (T3Payments::$partsData as $partName => $v2) {
            $result['total']['parts'][$partName] = array(
                'title' => T3Payments::$partsData[$partName]['title'],
                'count' => 0,
                'amount' => 0,
            );
        }
        $dates = array();

        foreach ($ar as $v) {
            if (!isset($result['dates'][$v['actions_date']])) {
                $result['dates'][$v['actions_date']] = array(
                    'parts' => array(),
                    'netAmount' => 0,
                );
                foreach (T3Payments::$partsData as $partName => $v2) {
                    $result['dates'][$v['actions_date']]['parts'][$partName] = array(
                        'title' => T3Payments::$partsData[$partName]['title'],
                        'count' => 0,
                        'amount' => 0,
                    );
                }
            }

            $result['dates'][$v['actions_date']]['netAmount'] += $v['actions_amount'];
            $result['total'] ['netAmount'] += $v['actions_amount'];

            $result['dates'][$v['actions_date']]['parts'][$v['part_name']]['count'] += $v['actions_count'];
            $result['total'] ['parts'][$v['part_name']]['count'] += $v['actions_count'];

            $result['dates'][$v['actions_date']]['parts'][$v['part_name']]['amount'] += $v['actions_amount'];
            $result['total'] ['parts'][$v['part_name']]['amount'] += $v['actions_amount'];
        }

        return $result;
    }

    public function getDetailsTableItemsQuery($query, $forCount = false) {
        $selects = array();
        $allParts = $query['conditions']['part']['type'] == 'all';
        $partsNeeded = $query['conditions']['part']['values'];
        foreach (T3Payments::$partsData as $part => $partData) {

            if (!$allParts && !in_array($part, $partsNeeded))
                continue;

            foreach ($partData['tables'] as $table) {
                $select = $this->database->select();
                $selects[] = $select;

                if (!$forCount) {
                    $fieldsToSelect = T3Payments::$tablesConnections['all'];
                    if (isset(T3Payments::$tablesConnections[$table])) {
                        foreach (T3Payments::$tablesConnections[$table] as $alias => $trueFieldName) {
                            $index = array_search($alias, $fieldsToSelect);
                            unset($fieldsToSelect[$index]);
                            $fieldsToSelect[$alias] = $trueFieldName;
                        }
                    }

                    $oldFieldsToSelect = $fieldsToSelect;
                    $fieldsToSelect = array();
                    foreach (T3Payments::$tablesConnections['all'] as $field)
                        if (!isset($oldFieldsToSelect[$field]))
                            $fieldsToSelect[] = $field;
                        else
                            $fieldsToSelect[$field] = $oldFieldsToSelect[$field];

                    $select->from($table, $fieldsToSelect);
                }else {
                    $select->from($table, array('c' => 'count(*)'));
                }

                foreach (T3Payments::$paymentDetailsConditions['all'] as $type) {

                    if ($type == 'action_datetime')
                        continue;

                    if (!isset($query['conditions'][$type]) || $query['conditions'][$type]['type'] == 'all')
                        continue;

                    if (
                    isset(T3Payments::$paymentDetailsConditions[$table]) &&
                    array_key_exists($type, T3Payments::$paymentDetailsConditions[$table])
                    ) {
                        if (T3Payments::$paymentDetailsConditions[$table][$type] === null)
                            continue;
                        else
                            $fieldName = T3Payments::$paymentDetailsConditions[$table][$type];
                    }else {
                        $fieldName = $type;
                    }

                    if ($query['conditions'][$type]['type'] == 'certain')
                        $select->where("$fieldName = ?", $query['conditions'][$type]['certain']);
                    else if ($query['conditions'][$type]['type'] == 'values' && !empty($query['conditions'][$type]['values'])) {
                        $values = dbQuote($this->database, $query['conditions'][$type]['values']);
                        $select->where("$fieldName in ($values)");
                    }
                }

                if (!empty($query['start_datetime']) || !empty($query['end_datetime'])) {

                    $type = 'action_datetime';
                    $needToCheckActionDatetime = true;
                    if (
                    isset(T3Payments::$paymentDetailsConditions[$table]) &&
                    array_key_exists($type, T3Payments::$paymentDetailsConditions[$table])
                    ) {
                        if (T3Payments::$paymentDetailsConditions[$table][$type] === null)
                            $needToCheckActionDatetime = false;
                        else
                            $fieldName = T3Payments::$paymentDetailsConditions[$table][$type];
                    }else {
                        $fieldName = $type;
                    }

                    if ($needToCheckActionDatetime) {

                        if (!empty($query['start_datetime']))
                            $select->where("$fieldName >= ?", $query['start_datetime']);

                        if (!empty($query['end_datetime']))
                            $select->where("$fieldName <= ?", $query['end_datetime']);
                    }
                }

                $select->where('payment_id = ?', $query['payment_id']);
            }
        }


        $unitedSelect = $this->database->select();
        $unitedSelect->union($selects, Zend_Db_Select::SQL_UNION_ALL);
        $unionTable = array('united' => new Zend_Db_Expr('(' . (string) $unitedSelect . ')'));
        $select = $this->database->select();

        if (!$forCount) {
            $select->from($unionTable);
        } else {
            $select->from($unionTable, array('c' => "sum(united.c)"));
        }

        if (!$forCount) {
            $select->limit($query['page_size'], ($query['_page'] - 1) * $query['page_size']);
            $select->order('united.action_datetime desc');
        }

        return $select;
    }

    public function & getDetailsTableItems($query) {

        $select = $this->getDetailsTableItemsQuery($query, false);
        $result = $this->database->query($select)->fetchAll();
        return $result;
    }

    public function getDetailsTableItemsCount($query) {

        $select = $this->getDetailsTableItemsQuery($query, true);
        $result = $this->database->query($select)->fetchAll();
        return empty($result) ? 0 : $result[0]['c'];
    }

    public function bodyExport($product) {

        $selects = array();

        $fieldsNeeded = array(
            'item_type',
            'action_datetime',
            'channel_id',
            'subaccount_id',
            'lead_id',
            'action_sum',
        );

        foreach (T3Payments::$bodyExportParts as $part) {

            foreach (T3Payments::$partsData[$part]['tables'] as $table) {

                $select = $this->database->select();
                $selects[] = $select;

                $fieldsToSelect = $fieldsNeeded;
                if (isset(T3Payments::$tablesConnections[$table])) {
                    foreach (T3Payments::$tablesConnections[$table] as $alias => $trueFieldName) {
                        $index = array_search($alias, $fieldsToSelect);
                        unset($fieldsToSelect[$index]);
                        $fieldsToSelect[$alias] = $trueFieldName;
                    }
                }

                $oldFieldsToSelect = $fieldsToSelect;
                $fieldsToSelect = array();
                foreach ($fieldsNeeded as $field)
                    if (!isset($oldFieldsToSelect[$field]))
                        $fieldsToSelect[] = $field;
                    else
                        $fieldsToSelect[$field] = $oldFieldsToSelect[$field];

                $select->from($table, $fieldsToSelect);

                $select->where('payment_id = ?', $this->id);
                $select->where('lead_product = ?', $product);
            }
        }

        $unitedSelect = $this->database->select();
        $unitedSelect->union($selects, Zend_Db_Select::SQL_UNION_ALL);
        $unionTable = array('united' => new Zend_Db_Expr('(' . (string) $unitedSelect . ')'));
        $select = $this->database->select();

        $select->from($unionTable);

        $select->joinLeft("leads_data_$product", "leads_data_$product.id = united.lead_id");

        $select->joinLeft("users_company_webmaster_subacc", "users_company_webmaster_subacc.id = united.subaccount_id", array('subaccount_name' => 'name')
        );

        $data = $this->database->query($select)->fetchAll();

        $paymentsGraphics = new Payments();

        foreach ($data as &$v) {
            $v['action_sum'] = number_format($v['action_sum'], 2, ',', ' ');
        }

        $idGet = $this->getIdGet();
        $productTitle = $this->database->fetchOne('select title from leads_type where name = ?', array($product));

        putCSVHeaders("Payment-$idGet-$productTitle.csv");

        die(tableToCSVString(
        $data, array(
            'item_type' => 'Item Type',
            'action_datetime' => 'Date/Time',
            'channel_id' => 'Channel Id',
            'subaccount_name' => 'Subaccount Name',
            'lead_id' => 'Lead Id',
            'action_sum' => 'Action Sum',
        ), true, array('id', 'subaccount_id')
        ));
    }

    public function getProducts() {

        $selects = array();

        foreach (T3Payments::$bodyExportParts as $part) {

            foreach (T3Payments::$partsData[$part]['tables'] as $table) {

                $select = $this->database->select();
                $selects[] = $select;
                $select->from($table, array('lead_product'));
                $select->where('payment_id = ?', $this->id);
            }
        }

        $unitedSelect = $this->database->select();
        $unitedSelect->union($selects, Zend_Db_Select::SQL_UNION_ALL);
        $unionTable = array('united' => new Zend_Db_Expr('(' . (string) $unitedSelect . ')'));
        $select = $this->database->select();

        $select->from($unionTable);
        $select->group('lead_product');

        return $this->database->fetchCol($select->__toString());
    }

    public function sendEmail($automatic, $emails = null) {

        if ($emails === null)
            $emails = T3Cache_CompanyUserContacts::getEmail($this->webmaster_id);

        if (!is_array($emails))
            $emails = EmailsParser::toArray($emails);

        $paymentsGraphics = new Payments();

        $messageObj = T3Mail::createMessage('payment', array(
            'content' => $paymentsGraphics->renderPaymentDocument($this, true)
        ));
        
        if (!empty($emails)){
            $messageObj->addToArray($emails);
        }

        $messageObj->addBccArray(array('hrant.m@t3leads.com'));

        $messageObj->SendMail();
    }

    public function initializeApprovementStatus() {


        $paymentsCount = $this->database->fetchOne('select count(*) from webmasters_payments group by webmaster_id');

        if ($paymentsCount <= 2) {
            $this->approvement_status = T3Payments_ApprovementRequest::STATUS_not_processed;
            $this->approvement_request_type = T3Payments::APPR_REQUEST_first_payments;
            $this->saveToDatabase();
            return;
        }


        $data = $this->database->fetchAll('select * from payments_approvement_requests where webmaster_id = ? && process_status = ?', array(
            $this->webmaster_id,
            T3Payments_ApprovementRequest::STATUS_not_processed,
        ));

        if (empty($data)) {
            return;
        }

        foreach ($data as $v) {

            $request = new T3Payments_ApprovementRequest();
            $request->fromArray($v);

            $request->payment_created = 1;
            $request->payment_id = $this->id;
            $request->saveToDatabase();

            $this->approvement_status = T3Payments_ApprovementRequest::STATUS_not_processed;
            $this->approvement_request_type = T3Payments::APPR_REQUEST_fraud_detector_request;
            $this->fraud_detector_request_id = $request->id;

            $this->saveToDatabase();
        }
    }

    public function getFraudDetectorRequest() {
        $request = new T3Payments_ApprovementRequest();
        $request->fromDatabase($this->fraud_detector_request_id);
        return $request;
    }

    public function processApprovement($approve) {

        if ($this->approvement_status == T3Payments_ApprovementRequest::STATUS_not_requested)
            return;

        if ($approve) {
            $this->approvement_status = T3Payments_ApprovementRequest::STATUS_approved;
        } else {
            $this->approvement_status = T3Payments_ApprovementRequest::STATUS_disapproved;
        }

        $this->approvement_process_user_id = T3Users::getCUser()->id;
        $this->approvement_process_datetime = mySqlDateTimeFormat();
        $this->saveToDatabase();


        if ($this->approvement_request_type == T3Payments::APPR_REQUEST_first_payments) {
            
        } else if ($this->approvement_request_type == T3Payments::APPR_REQUEST_fraud_detector_request) {

            $this->database->query('
              update payments_approvement_requests set 
                process_status = ?,
                process_datetime = ?,
                process_user_id = ?
              where payment_id = ?
            ', array(
                $this->approvement_status,
                $this->approvement_process_datetime,
                $this->approvement_process_user_id,
                $this->id,
            ));
        } 
        else {
            
        }
    } 
}