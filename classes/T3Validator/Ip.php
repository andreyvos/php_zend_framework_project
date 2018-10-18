<?php


class T3Validator_Ip extends T3Validator_Abstract{

    const NOT_IP_ADDRESS = 'notIpAddress';

    public function isValidCore($value)
    {
        $valueString = (string) $value;

        $this->_setValue($valueString);

        if (ip2long($valueString) === false) {
            $this->error(self::NOT_IP_ADDRESS);
            return false;
        }

        return true;
    }

}
