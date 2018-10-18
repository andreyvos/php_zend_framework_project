<?php

class T3Validator_Regex extends T3Validator_Abstract{

    const NOT_MATCH = 'regexNotMatch';

    protected $_pattern;

    public function getMessage($code){
      return self::INVALID_TEXT;
    }


    public function defInit($pattern)
    {
        $this->setPattern($pattern);
    }

    public function getPattern()
    {
        return $this->_pattern;
    }

    public function setPattern($pattern)
    {
        $this->_pattern = (string) $pattern;
        return $this;
    }

    public function isValidCore($value)
    {
        $valueString = (string) $value;

        $status = @preg_match($this->_pattern, $valueString);
        if (false === $status) {
            /**
             * @see Zend_Validate_Exception
             */
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("Internal error matching pattern '$this->_pattern' against value '$valueString'");
        }
        if (!$status) {
            $this->error(self::NOT_MATCH);
            return false;
        }
        return true;
    }

}
