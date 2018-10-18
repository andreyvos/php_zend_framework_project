<?php

class T3Validator_Date extends T3Validator_Abstract{
  
    const NOT_YYYY_MM_DD = 'dateNotYYYY-MM-DD';
    const FALSEFORMAT    = 'dateFalseFormat';

    protected $_format;
    protected $_locale;

    public function getMessage($code){
      switch($code){
        case self::FALSEFORMAT    : return '%title% has an invalid format';
        case self::NOT_YYYY_MM_DD : return '%title% hasn\'t YYYY-MM-DD format';
        default                   : return parent::getMessage($code);
      }
    }

    public function defInit($format = null, $locale = null){
      $this->setFormat($format);
      $this->setLocale($locale);
    }


    public function getLocale(){
      return $this->_locale;
    }

    public function setLocale($locale = null){
        if ($locale === null) {
            $this->_locale = null;
            return $this;
        }

        require_once 'Zend/Locale.php';
        if (!Zend_Locale::isLocale($locale, true)) {
            if (!Zend_Locale::isLocale($locale, false)) {
                require_once 'Zend/Validate/Exception.php';
                throw new Zend_Validate_Exception("The locale '$locale' is no known locale");
            }

            $locale = new Zend_Locale($locale);
        }

        $this->_locale = (string) $locale;
        return $this;
    }


    public function getFormat(){
        return $this->_format;
    }

    public function setFormat($format = null){
        $this->_format = $format;
        return $this;
    }

    public function isValidCore($value){
        $valueString = (string) $value;

        if (($this->_format !== null) or ($this->_locale !== null)) {
            require_once 'Zend/Date.php';
            if (!Zend_Date::isDate($value, $this->_format, $this->_locale)) {
                if ($this->_checkFormat($value) === false) {
                    $this->error(self::FALSEFORMAT);
                } else {
                    $this->error(self::INVALID);
                }
                return false;
            }
        } else {
            if(strlen($valueString)>=3 && strpos($valueString, '--')!==false || $valueString[0]=='-' || $valueString[strlen($valueString)-1]=='-'){
                $this->error(self::IS_EMPTY);
                return false;
            }
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valueString, $ar)) {
                $this->error(self::NOT_YYYY_MM_DD);
                return false;
            }

            list($year, $month, $day) = sscanf($valueString, '%d-%d-%d');

            if (!checkdate($month, $day, $year)) {
                $this->error(self::INVALID);
                return false;
            }
        }

        return true;
    }

    private function _checkFormat($value)
    {
        try {
            require_once 'Zend/Locale/Format.php';
            $parsed = Zend_Locale_Format::getDate($value, array(
                                                  'date_format' => $this->_format, 'format_type' => 'iso',
                                                  'fix_date' => false));
            if (isset($parsed['year']) and ((strpos(strtoupper($this->_format), 'YY') !== false) and
                (strpos(strtoupper($this->_format), 'YYYY') === false))) {
                $parsed['year'] = Zend_Date::_century($parsed['year']);
            }
        } catch (Exception $e) {
            // Date can not be parsed
            return false;
        }

        if (((strpos($this->_format, 'Y') !== false) or (strpos($this->_format, 'y') !== false)) and
            (!isset($parsed['year']))) {
            // Year expected but not found
            return false;
        }

        if ((strpos($this->_format, 'M') !== false) and (!isset($parsed['month']))) {
            // Month expected but not found
            return false;
        }

        if ((strpos($this->_format, 'd') !== false) and (!isset($parsed['day']))) {
            // Day expected but not found
            return false;
        }

        if (((strpos($this->_format, 'H') !== false) or (strpos($this->_format, 'h') !== false)) and
            (!isset($parsed['hour']))) {
            // Hour expected but not found
            return false;
        }

        if ((strpos($this->_format, 'm') !== false) and (!isset($parsed['minute']))) {
            // Minute expected but not found
            return false;
        }

        if ((strpos($this->_format, 's') !== false) and (!isset($parsed['second']))) {
            // Second expected  but not found
            return false;
        }

        // Date fits the format
        return true;
    }
}
