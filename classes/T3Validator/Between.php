<?php

class T3Validator_Between extends T3Validator_Abstract{

    const NOT_NUMBER         = 'notNumber';
    const NOT_BETWEEN        = 'notBetween';
    const NOT_BETWEEN_STRICT = 'notBetweenStrict';

    const VAR_MIN = 'min';
    const VAR_MAX = 'max';

    protected $_min;
    protected $_max;
    protected $_inclusive;

    public function getMessage($code){
      switch($code){
        case self::NOT_NUMBER         : return '%title% is not a number';
        case self::NOT_BETWEEN        : return '%title% violates limits';
        case self::NOT_BETWEEN_STRICT : return '%title% violates limits';
        default                       : return parent::getMessage($code);
      }
    }

    public function defInit($min, $max, $inclusive = true){
        $this->setMin($min)
             ->setMax($max)
             ->setInclusive($inclusive);
    }


    public function getMin(){
        return $this->_min;
    }

    public function setMin($min){
        $this->_min = $min;
        return $this;
    }

    public function getMax(){
        return $this->_max;
    }

    public function setMax($max){
        $this->_max = $max;
        return $this;
    }


    public function getInclusive(){
        return $this->_inclusive;
    }


    public function setInclusive($inclusive){
        $this->_inclusive = $inclusive;
        return $this;
    }

    public function isValidCore($value){

      if(!is_numeric($value)){
        $this->error(self::NOT_NUMBER);
        return false;
      }

      if ($this->_inclusive) {
          if ($this->_min > $value || $value > $this->_max) {
              $this->error(self::NOT_BETWEEN);
              return false;
          }
      } else {
          if ($this->_min >= $value || $value >= $this->_max) {
              $this->error(self::NOT_BETWEEN_STRICT);
              return false;
          }
      }
      return true;
    }

}
