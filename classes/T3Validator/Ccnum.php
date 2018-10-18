<?php


class T3Validator_Ccnum extends T3Validator_Abstract{

    const LENGTH   = 'ccnumLength';
    const CHECKSUM = 'ccnumChecksum';

    protected static $_filter;

    public function isValidCore($value){

        if (null === self::$_filter) {
            /**
             * @see Zend_Filter_Digits
             */
            require_once 'Zend/Filter/Digits.php';
            self::$_filter = new Zend_Filter_Digits();
        }

        $valueFiltered = self::$_filter->filter($value);

        $length = strlen($valueFiltered);

        if ($length < 13 || $length > 19) {
            $this->error(self::LENGTH);
            return false;
        }

        $sum    = 0;
        $weight = 2;

        for ($i = $length - 2; $i >= 0; $i--) {
            $digit = $weight * $valueFiltered[$i];
            $sum += floor($digit / 10) + $digit % 10;
            $weight = $weight % 2 + 1;
        }

        if ((10 - $sum % 10) % 10 != $valueFiltered[$length - 1]) {
            $this->error(self::CHECKSUM, $valueFiltered);
            return false;
        }

        return true;
    }

}
