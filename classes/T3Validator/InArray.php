<?php

class T3Validator_InArray extends T3Validator_Abstract{

    const NOT_IN_ARRAY = 'notInArray';

    protected $_haystack;
    protected $_strict;

    public function defInit($haystack, $strict = false){
        
        foreach($haystack as $k => $v)
          if(is_string($v))
            $haystack[$k] = trim(strtolower($v));

        $this->setHaystack($haystack)
             ->setStrict($strict);
    }

    public function getHaystack(){
        return $this->_haystack;
    }

    public function setHaystack( $haystack){
        $this->_haystack = $haystack;
        return $this;
    }

    public function getStrict(){
        return $this->_strict;
    }

    public function setStrict($strict){
        $this->_strict = $strict;
        return $this;
    }


    public function isValidCore($value){

        if(is_string($value))
          $value = trim(strtolower($value));

        if (!in_array($value, $this->_haystack, $this->_strict)) {
            $this->error(self::NOT_IN_ARRAY);
            return false;
        }
        return true;
    }

}
