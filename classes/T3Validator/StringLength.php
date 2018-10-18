<?php

class T3Validator_StringLength extends T3Validator_Abstract{

    const TOO_SHORT = 'stringLengthTooShort';
    const TOO_LONG  = 'stringLengthTooLong';

    protected $_min;
    protected $_max;

    public function getMessage($code){
      switch($code){
        case self::TOO_LONG : return '%title% is too long';
        case self::TOO_SHORT: return '%title% is too short';
        default             : return parent::getMessage($code);
      }
    }

    public function defInit($min = 0, $max = null)
    {
        $this->setMin($min);
        $this->setMax($max);
    }

    public function getMin()
    {
        return $this->_min;
    }

    public function setMin($min)
    {
        if (null !== $this->_max && $min > $this->_max) {
            /**
             * @see Zend_Validate_Exception
             */
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The minimum must be less than or equal to the maximum length, but $min >"
                                            . " $this->_max");
        }
        $this->_min = max(0, (integer) $min);
        return $this;
    }


    public function getMax()
    {
        return $this->_max;
    }

    public function setMax($max)
    {
        if (null === $max) {
            $this->_max = null;
        } else if ($max < $this->_min) {
            /**
             * @see Zend_Validate_Exception
             */
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The maximum must be greater than or equal to the minimum length, but "
                                            . "$max < $this->_min");
        } else {
            $this->_max = (integer) $max;
        }

        return $this;
    }

    public function isValidCore($value)
    {
        $valueString = (string) $value;

        $length = iconv_strlen($valueString);
        if ($length < $this->_min) {
            $this->error(self::TOO_SHORT);
        }
        if (null !== $this->_max && $this->_max < $length) {
            $this->error(self::TOO_LONG);
        }
    }

}
