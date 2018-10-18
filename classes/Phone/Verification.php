<?php

class Phone_Verification {
    static public function randVerifivationCode(){
        return rand(10000,99999);
    }

    /**
     * @param $phone
     * @return int
     * @throws Exception
     */
    static public function createVerification_SMS($phone){
        if(count($temp = explode(".", $phone)) !== 2)      throw new Exception('Invalid Phone');

        list($phoneCountryCode, $phoneNumber) = $temp;

        if(!is_numeric($phoneCountryCode))  throw new Exception('Invalid Phone Country Code');
        if(!is_numeric($phoneNumber))       throw new Exception('Invalid Phone Number');


        $verificationCode = self::randVerifivationCode();
        $message = "DestinationLeads code: {$verificationCode}";

        $fp = @fsockopen("ssl://api.telesign.com", 443, $errno, $errstr, 5);
        if ($fp) {

            $data = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
            $data.= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\r\n";
            $data.= "<soap:Body>\r\n";
            $data.= "<RequestSMS xmlns=\"https://www.telesign.com/api/\">\r\n";
            $data.= "<CustomerID>9E4C6EB0-22DB-4E40-8AB0-26339B1A9ABD</CustomerID>\r\n";
            $data.= "<AuthenticationID>E57C069B-7CC0-4D03-BFC1-FC4C0CE93852</AuthenticationID>\r\n";
            $data.= "<CountryCode>{$phoneCountryCode}</CountryCode>\r\n";
            $data.= "<PhoneNumber>{$phoneNumber}</PhoneNumber>\r\n";
            $data.= "<VerificationCode>{$verificationCode}</VerificationCode>\r\n";
            $data.= "<Message>{$message}</Message>\r\n";
            $data.= "</RequestSMS>\r\n";
            $data.= "</soap:Body>\r\n";
            $data.= "</soap:Envelope>";

            $write = "POST /1.x/soap.asmx HTTP/1.1\r\n";
            $write.= "Host: api.telesign.com\r\n";
            $write.= "Content-Type: text/xml; charset=utf-8\r\n";
            $write.= "Content-Length: " . strlen($data) . "\r\n";
            $write.= "SOAPAction: \"https://www.telesign.com/api/RequestSMS\"\r\n";
            $write.= "\r\n";
            $write.= $data;

            fwrite($fp, $write);
            $contents = fread($fp, 8192);

            fclose($fp);

            echo "!!" . $write . "!!";
            echo "\r\n\r\n\r\n";
            echo "!!" . $contents . "!!";

            return $verificationCode;
        }

        throw new Exception("Invalid Connection to api.telesign.com:443");
    }

    /**
     * @return T3PhoneVerification
     */
    static public function createVerification_Call($phone, $voiceLanguage = ''){
        if(count($temp = explode(".", $phone)) !== 2)      throw new Exception('Invalid Phone');

        list($phoneCountryCode, $phoneNumber) = $temp;

        if(!is_numeric($phoneCountryCode))  throw new Exception('Invalid Phone Country Code');
        if(!is_numeric($phoneNumber))       throw new Exception('Invalid Phone Number');


        $verificationCode = self::randVerifivationCode();

        $fp=fsockopen('ssl://api.telesign.com',443,$errno,$errstr,5);

        if ($fp) {
            $data = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
            $data.= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\r\n";
            $data.= "<soap:Body>\r\n";
            $data.= "<RequestCALL xmlns=\"https://www.telesign.com/api/\">\r\n";
            $data.= "<CustomerID>9E4C6EB0-22DB-4E40-8AB0-26339B1A9ABD</CustomerID>\r\n";
            $data.= "<AuthenticationID>E57C069B-7CC0-4D03-BFC1-FC4C0CE93852</AuthenticationID>\r\n";
            $data.= "<CountryCode>{$phoneCountryCode}</CountryCode>\r\n";
            $data.= "<PhoneNumber>{$phoneNumber}</PhoneNumber>\r\n";
            $data.= "<VerificationCode>{$verificationCode}</VerificationCode>\r\n";
            $data.= "<DelayTime>0</DelayTime>\r\n";
            $data.= "<RedialCount>0</RedialCount>\r\n";
            $data.= "<ExtensionContent></ExtensionContent>\r\n";
            $data.= "<ExtensionType></ExtensionType>\r\n";
            $data.= "<Message>{$voiceLanguage}</Message>\r\n";
            $data.= "</RequestCALL>\r\n";
            $data.= "</soap:Body>\r\n";
            $data.= "</soap:Envelope>";

            $write = "POST /1.x/soap.asmx HTTP/1.1\r\n";
            $write.= "Host: telesign.com\r\n";
            $write.= "Content-type: text/xml; charset=utf-8\r\n";
            $write.= "Content-Length: " . strlen($data) . "\r\n";
            $write.= "SOAPAction: \"https://www.telesign.com/api/RequestCALL\"\r\n";
            $write.= "\r\n";
            $write.= $data;

            fwrite($fp, $write);
            $contents = fread($fp, 8192);
            fclose($fp);

            echo "!!" . $write . "!!";
            echo "\r\n\r\n\r\n";
            echo "!!" . $contents . "!!";

            return $verificationCode;
        }

        throw new Exception("Invalid Connection to api.telesign.com:443");
    }
}