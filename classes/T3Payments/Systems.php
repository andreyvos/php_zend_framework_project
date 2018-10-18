<?php

TableDescription::addTable('webmasters_payments_systems', array(
    'webmaster_id', //  int(11)
    'systems_used', //  set('check','epass','paypal','webmoney','wire')
    'configured',
    'check_part', //  decimal(10,2)
    'check_data', //  text
    'epass_part', //  decimal(10,2)
    'epass_data', //  text
    'paypal_part', //  decimal(10,2)
    'paypal_data', //  text
    'webmoney_part', //  decimal(10,2)
    'webmoney_data', //  text
    'wire_part', //  decimal(10,2)
    'wire_data', //  text

    'ach_3_business_days_part',
    'ach_3_business_days_data',
    'ach_next_business_day_part',
    'ach_next_business_day_data',
    'ach_same_day_part',
    'ach_same_day_data',
), 'webmaster_id');

class T3Payments_Systems extends DbSerializable {

    public $id;
    public $webmaster_id;
    public $systems_used;
    public $configured;
    public $check_part;
    public $check_data;
    public $epass_part;
    public $epass_data;
    public $paypal_part;
    public $paypal_data;
    public $webmoney_part;
    public $webmoney_data;
    public $wire_part;
    public $wire_data;

    /*  const SYS_ACH_3_BUSINESS_DAYS = 'ach_3_business_days';
      const SYS_ACH_NEXT_BUSINESS_DAY = 'ach_next_business_day';
      const SYS_ACH_SAME_DAY = 'ach_same_day'; */
    public $ach_3_business_days_part;
    public $ach_3_business_days_data;
    public $ach_next_business_day_part;
    public $ach_next_business_day_data;
    public $ach_same_day_part;
    public $ach_same_day_data;
    public $webmaster;
    public $parts;
    public $data = array(
        T3Payments::SYS_CHECK => null,
        T3Payments::SYS_EPASS => null,
        T3Payments::SYS_PAYPAL => null,
        T3Payments::SYS_WEBMONEY => null,
        T3Payments::SYS_WIRE => null,
        T3Payments::SYS_ACH_3_BUSINESS_DAYS => null,
        T3Payments::SYS_ACH_NEXT_BUSINESS_DAY => null,
        T3Payments::SYS_ACH_SAME_DAY => null,
    );
    public static $duplicatingGroups = array(
        array(
            T3Payments::SYS_ACH_3_BUSINESS_DAYS,
            T3Payments::SYS_ACH_NEXT_BUSINESS_DAY,
            T3Payments::SYS_ACH_SAME_DAY,
        ),
    );

    public function __construct() {

        parent::__construct();
        $this->tables = array('webmasters_payments_systems');

        $this->parts = array(
            T3Payments::SYS_CHECK => & $this->check_part,
            T3Payments::SYS_EPASS => & $this->epass_part,
            T3Payments::SYS_PAYPAL => & $this->paypal_part,
            T3Payments::SYS_WEBMONEY => & $this->webmoney_part,
            T3Payments::SYS_WIRE => & $this->wire_part,
            T3Payments::SYS_ACH_3_BUSINESS_DAYS => & $this->ach_3_business_days_part,
            T3Payments::SYS_ACH_NEXT_BUSINESS_DAY => & $this->ach_next_business_day_part,
            T3Payments::SYS_ACH_SAME_DAY => & $this->ach_same_day_part,
        );

        $this->id = & $this->webmaster_id;

        $this->readNewIdAfterInserting = false;
    }

    public function getWebmaster($lazy = true) {
        if ($lazy && $this->webmaster !== null)
            return $this->webmaster;
        $this->webmaster = new T3WebmasterCompany();
        $this->webmaster->fromDatabase($this->webmaster_id);
        return $this->webmaster;
    }

    public function fromArray(&$array) {
        parent::fromArray($array);
        $this->systems_used = pregCommaSplit($this->systems_used);
        foreach ($this->data as $k => $v) {
            $var = "{$k}_data";
            $this->data[$k] = unserialize($this->$var);
            
            if($this->data[$k] === false){
                //echo "!NOTICE from Webmaster: {$this->webmaster_id}!";
            }
        }
    }

    public function verifyParts() {

        if (!$this->configured)
            return false;

        $sum = 0.0;

        foreach ($this->systems_used as $v) {
            if (!array_key_exists($v, $this->parts)) {
                // TODO
                // ошибка администратора (нарушение целостности)
            }
            $sum += $this->parts[$v];
        }
        if ($sum != 1.0) {
            // TODO
            // ошибка администратора (нарушение целостности)
            //throw new Exception();
            return false;
        }
        return true;
    }

    public function toArray($tables = null) {
        //$this->verifyParts();
        $a = $this->systems_used;
        $this->systems_used = implode(',', $a);
        foreach ($this->data as $k => $v) {
            $var = "{$k}_data";
            $this->$var = serialize($v);
        }
        $result = parent::toArray($tables);
        $this->systems_used = $a;
        return $result;
    }

    public function split($sum, &$withFee, &$withoutFee, &$fee) {
        if (!$this->configured)
            return false;

        if (!$this->verifyParts())
            return false;

        $sumBack = 0.0;
        $withFee = array();
        $withoutFee = array();
        $fee = array();
        foreach ($this->systems_used as $v) {
            $withFee[$v] = $sum * $this->parts[$v];
            $sumBack += $withFee[$v];
            $last = $v;
        }

        if ($sumBack < $sum) {
            $withFee[$last] += $sum - $sumBack;
        }

        $needToTakeFee = (bool) (T3Payments::getInstance()->needToTakeFeeFrom($this->webmaster_id));

        $data = T3Payments::getInstance()->getSystemsData();
        foreach ($withFee as $system => $systemSum) {
            if ($needToTakeFee)
                $fee[$system] = ($systemSum - $data[$system]['commision_const']) * ($data[$system]['commision_rate'] * 0.01) + $data[$system]['commision_const'];
            else
                $fee[$system] = 0.0;
            $withoutFee[$system] = $systemSum - $fee[$system];
        }

        return true;
    }

    public function getMinimum() {
        if ($this->configured && count($this->systems_used) != 0) {
            $result = 0;
            $data = T3Payments::getInstance()->getSystemsData();
            
            foreach ($this->systems_used as $system) {
                $result += $data[$system]['minimal'];
            }
            
            return $result;
        } 
        else {
            return T3Payments::getInstance()->getMinimumOfAllSystems();
        }
    }

    public function fillDefault($webmasterId) {
        $this->webmaster_id = $webmasterId;
        $this->systems_used = array();
        $this->configured = 0;
        $this->check_part = null;
        $this->check_data = null;
        $this->epass_part = null;
        $this->epass_data = null;
        $this->paypal_part = null;
        $this->paypal_data = null;
        $this->webmoney_part = null;
        $this->webmoney_data = null;
        $this->wire_part = null;
        $this->wire_data = null;
        $this->ach_3_business_days_part = null;
        $this->ach_3_business_days_data = null;
        $this->ach_next_business_day_part = null;
        $this->ach_next_business_day_data = null;
        $this->ach_same_day_part = null;
        $this->ach_same_day_data = null;
    }

    /**
     * Получить суммы которые надо выплатить на каждую платежную систему
     *
     */
    public function getPaymentSystemsValues($total) {
        $total = round($total, 2);

        $result = array();

        $partsPersents = array();
        $lastSystem = null;

        foreach ($this->parts as $sys => $percent) {
            if ($percent > 0) {
                $partsPersents[$sys] = $percent;
                $lastSystem = $sys;
            }
        }

        if (count($partsPersents) == 1) {
            $result[$lastSystem] = $total;
        } else if (count($partsPersents) > 1) {
            $lastPersents = $partsPersents[$lastSystem];
            unset($partsPersents[$lastSystem]);

            $currentTotal = 0;
            foreach ($partsPersents as $sys => $persents) {
                $tempValue = round($persents * $total, 2);
                $currentTotal+= $tempValue;
                $result[$sys] = $tempValue;
            }

            $result[$lastSystem] = $total - $currentTotal;
        }

        return $result;
    }

    public static function partiallyHideWebmoneyNumber($number) {
        $n = strlen($number) - 4;
        for ($i = 1; $i < $n; $i++)
            $number[$i] = '*';
        return $number;
    }

    public static function partiallyHideBankAccountNumber($number) {
        $n = strlen($number) - 4;
        for ($i = 0; $i < $n; $i++)
            $number[$i] = '*';
        return $number;
    }

    public static function partiallyHideBankSwift($number) {
        $n = strlen($number) - 4;
        for ($i = 0; $i < $n; $i++)
            $number[$i] = '*';
        return $number;
    }

    public function equals(T3Payment_Systems $other) {

        if ($this->systems_used != $other->systems_used)
            return false;

        foreach ($this->systems_used as $v) {
            if ($this->parts[$v] != $other->parts[$v])
                return false;
            if ($this->data[$v] != $other->data[$v])
                return false;
        }

        return true;
    }

    public static function getConvolution(&$data) {
        if (is_array($data)) {
            $result = "";
            foreach ($data as $v)
                $result .= T3Payments_Systems::getConvolution($v);
            return $result;
        } else {
            return preg_replace('/\W+/i', '', $data);
        }
    }

}

