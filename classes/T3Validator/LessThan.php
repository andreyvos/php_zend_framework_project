<?php

class T3Validator_LessThan extends T3Validator_Abstract
{

    const NOT_LESS = 'notLessThan';

    protected $_max;

    public function defInit($max)
    {
        $this->setMax($max);
    }

    public function getMax()
    {
        return $this->_max;
    }


    public function setMax($max)
    {
        $this->_max = $max;
        return $this;
    }

    public function isValidCore($value)
    {
        if ($this->_max <= $value) {
            $this->error(self::NOT_LESS);
            return false;
        }
        return true;
    }

}
