<?php

class T3Validator_GreaterThan extends T3Validator_Abstract{

    const NOT_GREATER = 'notGreaterThan';

    protected $_min;

    public function defInit($min)
    {
        $this->setMin($min);
    }

    public function getMin()
    {
        return $this->_min;
    }

    public function setMin($min)
    {
        $this->_min = $min;
        return $this;
    }


    public function isValidCore($value)
    {

        if ($this->_min >= $value) {
            $this->error(self::NOT_GREATER);
            return false;
        }
        return true;
    }

}
