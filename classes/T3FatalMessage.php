<?php

class T3FatalMessage {
    static public $mans = array();

    static public function getUsers(){
        $result = array();
        $all = T3Db::api()-> fetchAll("select * from fatal_errors_users");
        
        if(count($all)){
            foreach($all as $u){
                $result[$u['alias']] = $u;    
            }
        }
        
        return $result;    
    }
    
    static public function renderTextFromSerialize($str){
        $return = '';
        
        $array = unserialize($str); 
        
        if(count($array) == 0){
            $return = '<span style="color:#BBB">No</span>';         
        }
        else {
            $usersMultiOptions = self::getUsersMultiOptionsArray();
            
            foreach($array as $a){
                if(strlen($return)!=0)$return.= ", ";
                $return.= $usersMultiOptions[$a];    
            } 
        } 
        
        return $return;      
    }
    
    static public function getUsersMultiOptionsArray(){
        $result = array();
        
        $all = self::getUsers();
        if(count($all)){
            foreach($all as $el){
                $result[$el['alias']] = $el['name'];
            }    
        }
        
        return $result;
    }
    
    
    static public function sendMessage($name, $options = array(), $toSms = true, $toEmail = true){
        $message = T3Db::api()->fetchRow("select email_subject, email_text, sms_text, email_to, sms_to from fatal_errors_messages where `name`=?", $name);
        
        if($message !== false){
            $email_subject  = $message['email_subject']; 
            $email_text     = $message['email_text'];
            $sms_text       = $message['sms_text'];
            $email_to       = unserialize($message['email_to']);
            $sms_to         = unserialize($message['sms_to']);
            
            if(is_array($options) && count($options)){
                foreach($options as $key => $opt){
                    $email_text = str_replace('{' . $key . '}', (string)$opt, $email_text);
                    $sms_text   = str_replace('{' . $key . '}', (string)$opt, $sms_text);    
                }  
            } 
            
            if($toEmail) self::sendEmail($email_text, $email_to, $email_subject);
            if($toSms)   self::sendSMS($sms_text, $sms_to);
        }
    }
    
    static public function sendMessageAsync($name, array $options = array()){
        $host = $_SERVER['HTTP_HOST']; 
        
        $fp = fsockopen($host, 80, $errno, $errstr, 5);
        if ($fp) {
            $out = "GET /system/fatal_errors_async_send.php?name=" . urlencode($name) . "&options=" . urlencode(Zend_Json::encode($options)) . " HTTP/1.1\r\n";
            $out .= "Host: {$host}\r\n\r\n";
            fwrite($fp, $out);
            fclose($fp);
        }         
    }
    
    
    static public function sendEmail($text, $to = 'all', $subject = null){
        self::$mans = self::getUsers();
        if($to == 'all')$to = array_keys(self::$mans);
        
        if(is_array($to) && count($to) && strlen($text)){  
            $toArray = array();
            foreach($to as $man){
                if(isset(self::$mans[$man]) && strlen(self::$mans[$man]['email'])){
                    $toArray[] = array(
                        'email' => self::$mans[$man]['email'], 
                        'name'  => self::$mans[$man]['name']
                    );
                }
            }
            
            if(count($toArray)){
                $message = T3Mail::createMessage('fatalMessage', array(
                    'text' => $text,
                ));
                
                if(strlen($subject)){
                    $message->setSubject($subject);
                }
                
                $message->addToArray($toArray)
                ->SendMail();     
            }
        }    
    }
    
    
    static public function sendSMS($text, $to = 'all'){
        self::$mans = self::getUsers(); 
        if($to == 'all')$to = array_keys(self::$mans);
        
        if(is_array($to) && count($to) && strlen($text)){  
            foreach($to as $man){
                if(isset(self::$mans[$man]) && strlen(self::$mans[$man]['cell'])){
                    $a = explode(".", self::$mans[$man]['cell']);
                    if(count($a) == 2){
                        $fp = @fsockopen("ssl://api.telesign.com", 443, $errno, $errstr, 5);
                        if ($fp) {
                            $data = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
                            $data.= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\r\n";
                            $data.= "<soap:Body>\r\n";
                            $data.= "<RequestSMS xmlns=\"https://www.telesign.com/api/\">\r\n";
                            $data.= "<CustomerID>9E4C6EB0-22DB-4E40-8AB0-26339B1A9ABD</CustomerID>\r\n";
                            $data.= "<AuthenticationID>E57C069B-7CC0-4D03-BFC1-FC4C0CE93852</AuthenticationID>\r\n";
                            $data.= "<CountryCode>{$a['0']}</CountryCode>\r\n";
                            $data.= "<PhoneNumber>{$a['1']}</PhoneNumber>\r\n";
                            $data.= "<VerificationCode>" . rand(10000, 99999) . "</VerificationCode>\r\n";
                            $data.= "<Message>{$text}</Message>\r\n";
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
 
                        } 
                    }
                }  
            }
        } 
    }   
}