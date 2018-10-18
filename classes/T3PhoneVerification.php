<?php

TableDescription::addTable('users_phones_verification', array(
    'id',
    'iduser',
    'phone',
    'create_datetime',
    'response',
    'request',
    'code',
    'status',
    'type',
));


class T3PhoneVerification extends DbSerializable{

    const SID           = 'AC7e8f86ac7001bda852b6b0f4bf6de988';
    const TOKEN         = '166e4713d358198894d39d1519e5342d';
    const FROM_NUMBER   = '+18182736424';

    public $id;
    public $iduser;
    public $phone;
    public $create_datetime;
    public $response;
    public $request;
    public $code;
    public $status = 'wait';
    public $type;
    
    public function __construct($product = null) {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('users_phones_verification'); 
    }
    
    static public function getVoiceLanguages(){
        return array(
            /*
            't3leads'       =>  'English',
            'Arabic'        =>  'Arabic',
            'Chinese'       =>  'Chinese',
            'French'        =>  'French',
            'German'        =>  'German',
            'Hebrew'        =>  'Hebrew',
            'Hindi'         =>  'Hindi',
            'Hungarian'     =>  'Hungarian',
            'Italian'       =>  'Italian',
            'Japanese'      =>  'Japanese',
            'Korean'        =>  'Korean',
            'Polish'        =>  'Polish',
            'Portuguese'    =>  'Portuguese',
            'Romanian'      =>  'Romanian',
            'Russian'       =>  'Russian',
            'Spanish'       =>  'Spanish',
            'Ukranian'      =>  'Ukranian',
            'Vietnamese'    =>  'Vietnamese',
            */

            'en-US' => 'English',
            'da-DK' => 'Dansk - Danish',
            'de-DE' => 'Deutsch - German',
            'es-ES' => 'Español - Spanish',
            'fr-FR' => 'Français - French',
            'it-IT' => 'Italiano - Italian',
            'nb-NO' => 'Norsk - Norwegian',
            'nl-NL' => 'Nederlands - Dutch',
            'pl-PL' => 'Polski - Polish',
            'pt-PT' => 'Português - Portuguese',
            'sv-SE' => 'Svenska - Swedish',
            'fi-FI' => 'Suomi - Finnish',
            'ru-RU' => 'Русский - Russian',
            'zh-CN' => '中國 - Chinese',
            'ja-JP' => '日本人 - Japanese',
            'ko-KR' => '한국의 - Korean',

        );   
    }
    
    static public function randVerifivationCode(){
        return rand(10000,99999);    
    }
    
    /**
    * @return T3PhoneVerification
    */
    static public function createVerification_SMS($phoneCountryCode, $phoneNumber, $idUser = 0){
        $verificationCode = self::randVerifivationCode();
        $message = "Your code is: {$verificationCode}. Please enter this code in your account at T3leads.com";
        
        return self::createVerification($idUser, 'sms', $phoneCountryCode, $phoneNumber, $message, $verificationCode);        
    }
    
    /**
    * @return T3PhoneVerification
    */
    static public function createVerification_Call($phoneCountryCode, $phoneNumber, $voiceLanguage = '', $idUser = 0){
        return self::createVerification($idUser, 'call', $phoneCountryCode, $phoneNumber, $voiceLanguage);       
    }
    
    /**
    * @return T3PhoneVerification
    */
    static protected function createVerification($idUser, $type, $phoneCountryCode, $phoneNumber, $message = null, $verificationCode = null){
        if(!$verificationCode) $verificationCode = self::randVerifivationCode();
        
        $obj = new T3PhoneVerification();
        
        $obj->iduser = $idUser;
        $obj->phone = "{$phoneCountryCode}.{$phoneNumber}";
        $obj->create_datetime = date("Y-m-d H:i:s");
        $obj->code = $verificationCode;
        $obj->type = $type;
        
        if($obj->type == 'sms'){
            /*
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
                
                $obj->request = $write;
                $obj->response = $contents;   
            }
            else { 
                $obj->status = 'error';
            }
            */

            $request = array(
                'account_sid' => self::SID,
                'auth_token'  => self::TOKEN,
                'data' => array(
                    'To'            => "+{$phoneCountryCode}{$phoneNumber}",
                    'From'          => self::FROM_NUMBER,
                    'Body'          => "T3Leads code: {$verificationCode}",
                )
            );

            $twilio = new Services_Twilio($request['account_sid'], $request['auth_token']);
            $response = $twilio->account->messages->create($request['data']);

            $obj->request  = Zend_Json::encode((array)$request);
            $obj->response = Zend_Json::encode((array)$response);

        }
        else {
            /*
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
                $data.= "<Message>{$message}</Message>\r\n"; 
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
                
                $obj->request = $write;
                $obj->response = $contents; 
            }
            else { 
                $obj->status = 'error';
            }
            */

            $request = array(
                'account_sid' => self::SID,
                'auth_token'  => self::TOKEN,
                'To'          => "+{$phoneCountryCode}{$phoneNumber}",
                'From'        => self::FROM_NUMBER,
                'Code'        => "{$verificationCode}",
                'Lang'        => $message,
            );

            $twilio = new Services_Twilio($request['account_sid'], $request['auth_token']);
            $response = $twilio->account->calls->create(
                    $request['From'],
                    $request['To'],
                    "https://account.t3leads.com/system/twillio/phone_verification.php?" .
                        "lang="  . urlencode($request['Lang']) .
                        "&code=" . urlencode($request['Code']) .
                        "&name=" . urlencode(T3Users::getUserById($idUser)->first_name) ,
                    array(
                        'Method'                => 'POST',
                        'Record'                => 'false',
                    )
                );

            $obj->request  = Zend_Json::encode((array)$request);
            $obj->response = Zend_Json::encode((array)$response);
        }
        
        $obj->insertIntoDatabase();
        
        return $obj;   
    }    
}