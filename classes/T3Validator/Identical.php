<?php

class T3Validator_Identical extends T3Validator_Abstract{

    const NOT_SAME      = 'notSame';
    const MISSING_TOKEN = 'missingToken';


    protected $_token;


    public function defInit($token = null)
    {
        if (null !== $token) {
            $this->setToken($token);
        }
    }

    public function setToken($token)
    {
        $this->_token = (string) $token;
        return $this;
    }

    public function getToken()
    {
        return $this->_token;
    }


    public function isValidCore($value)
    {
        $token = $this->getToken();

        if (emptyNotZero($token)) {
            $this->error(self::MISSING_TOKEN);
            return false;
        }

        if ($value !== $token)  {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }
}
