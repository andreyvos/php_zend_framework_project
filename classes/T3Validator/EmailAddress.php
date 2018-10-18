<?php

class T3Validator_EmailAddress extends T3Validator_Abstract{

    const INVALID_EMAIL      = 'emailAddressInvalid';
    const INVALID_HOSTNAME   = 'emailAddressInvalidHostname';
    const INVALID_MX_RECORD  = 'emailAddressInvalidMxRecord';
    const DOT_ATOM           = 'emailAddressDotAtom';
    const QUOTED_STRING      = 'emailAddressQuotedString';
    const INVALID_LOCAL_PART = 'emailAddressInvalidLocalPart';


    public $hostnameValidator;
    protected $_validateMx = false;
    protected $_hostname;
    protected $_localPart;


    public function getMessage($code){
      return self::INVALID_TEXT;
    }

    public function defInit($allow = Zend_Validate_Hostname::ALLOW_DNS, $validateMx = false, Zend_Validate_Hostname $hostnameValidator = null){
        $this->setValidateMx($validateMx);
        $this->setHostnameValidator($hostnameValidator, $allow);
    }

    public function setHostnameValidator(Zend_Validate_Hostname $hostnameValidator = null, $allow = Zend_Validate_Hostname::ALLOW_DNS){
        if ($hostnameValidator === null) {
            $hostnameValidator = new Zend_Validate_Hostname($allow);
        }
        $this->hostnameValidator = $hostnameValidator;
    }

    public function validateMxSupported()
    {
        return function_exists('dns_get_mx');
    }

    public function setValidateMx($allowed)
    {
        $this->_validateMx = (bool) $allowed;
    }

    public function isValidCore($value)
    {
        $valueString = (string) $value;

        // Split email address up
        if (!preg_match('/^(.+)@([^@]+)$/', $valueString, $matches)) {
            $this->error(self::INVALID_EMAIL);
            return false;
        }

        $this->_localPart = $matches[1];
        $this->_hostname  = $matches[2];

        // Match hostname part*
        $hostnameResult = $this->hostnameValidator/*->setTranslator($this->getTranslator())*/
                               ->isValid($this->_hostname);
        if (!$hostnameResult) {
            $this->error(self::INVALID_HOSTNAME);

            /*foreach ($this->hostnameValidator->getErrors() as $error) {
              $this->error(Report_Codes::ERROR, $error);
            }*/
        }

        // MX check on hostname via dns_get_record()
        if ($this->_validateMx) {
            if ($this->validateMxSupported()) {
                $result = dns_get_mx($this->_hostname, $mxHosts);
                if (count($mxHosts) < 1) {
                    $hostnameResult = false;
                    $this->error(self::INVALID_MX_RECORD);
                }
            } else {
                /**
                 * MX checks are not supported by this system
                 * @see Zend_Validate_Exception
                 */
                require_once 'Zend/Validate/Exception.php';
                throw new Zend_Validate_Exception('Internal error: MX checking not available on this system');
            }
        }

        // First try to match the local part on the common dot-atom format
        $localResult = false;

        // Dot-atom characters are: 1*atext *("." 1*atext)
        // atext: ALPHA / DIGIT / and "!", "#", "$", "%", "&", "'", "*",
        //        "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~"
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d';
        if (preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $this->_localPart)) {
            $localResult = true;
        } else {
            // Try quoted string format

            // Quoted-string characters are: DQUOTE *([FWS] qtext/quoted-pair) [FWS] DQUOTE
            // qtext: Non white space controls, and the rest of the US-ASCII characters not
            //   including "\" or the quote character
            $noWsCtl    = '\x01-\x08\x0b\x0c\x0e-\x1f\x7f';
            $qtext      = $noWsCtl . '\x21\x23-\x5b\x5d-\x7e';
            $ws         = '\x20\x09';
            if (preg_match('/^\x22([' . $ws . $qtext . '])*[$ws]?\x22$/', $this->_localPart)) {
                $localResult = true;
            } else {
                $this->error(self::DOT_ATOM);
                $this->error(self::QUOTED_STRING);
                $this->error(self::INVALID_LOCAL_PART);
            }
        }

        // If both parts valid, return true
        if ($localResult && $hostnameResult) {
            return true;
        } else {
            return false;
        }
    }

}
