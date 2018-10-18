<?php

TableDescription::addTable('mail_messages', array(
   'id',
   'createDate',
   'subject',  
   'messageText', 
   'attachments',
   'params' 
));       
 

class T3Mail_Message extends DbSerializable {
    
    const defaultDomain     = 'account.t3leads.com';
    const showPageURL       = "/default/index/show-email-text/id/{id}/key/{key}"; 
    const visitWebsiteURL   = "/"; 
    const unsubscribeURL    = "/index/unsubscribe/";
    
    static public $debug = false;
    
    public $id; 
    public $createDate; 
    public $subject;
    
    /** @var T3Mail_Tempalte_Global */
    public $templateGlobal = null;
    static protected $templateGlobalDefault = array('id' => 2, 'name' => 'blank');
    
    /** @var T3Mail_Template_Message */    
    public $templateMessage = null; 
    
    public $messageText;
    
    public $fromEmail = 't3leads@t3leads.com'; 
    public $fromName = 'T3Leads'; 
    
    protected $attachments = array(); 
    public $params = array();
    
    public $to = array();
    protected $cc = array();
    protected $bcc = array();
    
    protected $dirAttacmentFiles; 

    /**
    * @return T3Mail_Message
    */
    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('mail_messages'); 
        
        $this->dirAttacmentFiles = T3SYSTEM_ROOT . DS . 'files' . DS . 'mail';
    }
    
    static public function searchTempalateLayout($name){
        if($name !== 0 && strlen($name)){
            if(is_numeric($name))   $condition = "id=?";
            else                    $condition = "name=?";
            
            $searchName = T3System::getConnect()->fetchRow("select id,name from mail_templates_global where {$condition}", $name);
            
            if($searchName) return $searchName; 
        }    
        
        return self::$templateGlobalDefault;            
    }
    
    /**
    * Загрузка Темплейта
    * 
    * @param mixed $name
    */
    public function loadTemplate($name){
        $template =  self::searchTempalateLayout($name);
        
        $this->templateGlobal = new T3Mail_Template_Global();
        $this->templateGlobal->fromDatabase($template['id']); 
    }
    
    /**
    * Загрузка Сообщения
    * 
    * @param mixed $name
    */
    public function loadMessage($name){
        if($name){
            if(is_numeric($name))   $condition = array('id' => $name);
            else                    $condition = array('name' => $name);
            
            $this->templateMessage = new T3Mail_Template_Message();
            $this->templateMessage->fromDatabase($condition); 
            
            $this->fromName = $this->templateMessage->fromName;  
            $this->fromEmail = $this->templateMessage->fromEmail;  
            
            $this->loadTemplate($this->templateMessage->layoutTemplateID);
        }
    }
    
    public function renderMessage($hideNotUseValues = true){
        $this->attachments = array();
        
        $this->messageText = '';
        
        if($this->templateMessage instanceof T3Mail_Template_Message){
            $this->messageText = $this->templateMessage->render($this->params);
            
            if(is_array($this->templateMessage->attachments) && count($this->templateMessage->attachments)){
                $this->attachments = array_merge($this->attachments, $this->templateMessage->attachments);
            }
        }
        
        if($this->templateGlobal instanceof T3Mail_Template_Global){
            
            if($this->messageText){
                $params = array_merge(array('content' => $this->messageText), $this->params);
            }
            else {
                $params = $this->params;  
            }
            
            $this->subject = $this->templateGlobal->subject;
            $this->messageText = $this->templateGlobal->render($params);
            
            if(is_array($this->templateGlobal->attachments) && count($this->templateGlobal->attachments)){
                $this->attachments = array_merge($this->attachments, $this->templateGlobal->attachments);
            }
        }
        
        if($this->templateMessage instanceof T3Mail_Template_Message){
            $this->subject = $this->templateMessage->subject;        
        }

        if(isset($params) && is_array($params) && count($params)){
            foreach($params as $name => $value){
                $this->subject = str_replace('{' . $name . '}', $value, $this->subject);
            }
        }
        
        if($hideNotUseValues) $this->subject = preg_replace("/{([a-z0-9_])*}/i", "", $this->subject);
        if($hideNotUseValues) $this->messageText = preg_replace("/{([a-z0-9_])*}/i", "", $this->messageText);
    }
    
    
    public function toArray($tables = null){
        if(is_null($this->createDate)) $this->createDate = date("Y-m-d H:i:s");
        
        $temp = $this->getParams();
        
        $this->attachments = serialize($this->attachments); 
        $this->params = serialize($this->params); 
        
        $return = parent::toArray($tables);
        $this->setParams($temp);
        
        return $return;
    }

    
    public function fromArray($array){
        parent::fromArray($array);
        
        $this->attachments = unserialize($this->attachments);    
    }
    
    public function findNotUseValues(){
        preg_match_all("/{((?:[a-z0-9_])*)}/i", $this->messageText, $matches);
        return array_values(array_unique($matches[1]));  
    }
    
    public function addAttachment($file){
        if(is_file($file)){
            $this->attachments[] = $file;
        }
        else if(is_file($this->dirAttacmentFiles . DS . $file)){
            $this->attachments[] = $this->dirAttacmentFiles . DS . $file;     
        }
        else {
            return false;    
        }
        
        return true;    
    }
    
    public function addAttachments(array $files){
        if(count($files)){
            foreach($files as $file){
                $this->addAttachment($file);
            }   
        }
        
        return $this; 
    }
    
    /**
    * @return T3Mail_Message
    */
    public function setSubject($subject){
        $this->subject = $subject;
        return $this;    
    }
    
    /**
    * @return T3Mail_Message
    */
    public function setFromName($name){
        $this->fromName = $name;
        return $this;    
    }
    
    /**
    * @return T3Mail_Message
    */
    public function setFromEmail($email){
        $this->fromEmail = $email;
        return $this;    
    }
    
    /**
    * @return T3Mail_Message
    */
    public function setMessageParams($array = null){
        if(is_array($array) && count($array)){
            $this->params = $array;
        }    
        return $this;
    }
    
    /**
    * @return T3Mail_Message
    */
    protected function addEmail($type, $email, $name = null, $clear = false){
        if($clear)$this->$type = array();
        
        array_push($this->$type, array(
            'email' => $email, 
            'name' => $name, 
        ));
        
        return $this;            
    }
    
    /**
    * @return T3Mail_Message
    */
    protected function addEmailsArray($type, array $array, $clear = false){
        if($clear)$this->$type = array();
        if(count($array)){
            foreach($array as $el){
                if(is_string($el)){
                    $el = array('email' => $el);
                       
                }
                
                if(isset($el['email'])){
                    $this->addEmail(
                        $type,
                        $el['email'],
                        ifset($el['name'])
                    );
                } 
            }  
        } 
        return $this;         
    }
    
    /**
    * @return T3Mail_Message
    */
    protected function addEmailsString($type, $string, $clear = false){
        if($clear)$this->$type = array();
        
        $array = array();
        $arr = explode("," , $string);
        
        foreach($arr as $el){
            $line = trim($el);
            
            if(strlen($line)){
                $matches = array();
                preg_match('/^(.*)(?:\<|\()[ ]*((?:[0-9a-zA-Z]|\.|\-|\_){1,128}@(?:[0-9a-zA-Z]|\.|\-){1,120}\.[a-zA-Z]{2,4})[ ]*(?:\>|\))$/i', $line, $matches); 
                
                if(count($matches) == 3){
                    $name = null;
                    if(strlen(trim($matches[1])))$name = trim($matches[1]);
                    $array[] = array(
                        'email' => $matches[2],
                        'name'  => $name,
                    );
                }
                else {
                    $matches = array(); 
                    preg_match("/((?:[0-9a-zA-Z]|\.|\-|\_){1,128}@(?:[0-9a-zA-Z]|\.|\-){1,120}\.[a-zA-Z]{2,4})/i", $line, $matches);
                    if(count($matches) == 2){
                        $array[] = array(
                            'email' => $matches[1],
                            'name'  => null,
                        );
                    }    
                }  
            }    
        }
        
        if(count($array)){
            $this->addEmailsArray($type, $array, $clear);      
        } 
         
        return $this;         
    }
    
    /** --- TO --- **/ 
    /** @return T3Mail_Message */
    public function addTo($email, $name = null){return $this->addEmail('to', $email, $name);} 
    /** @return T3Mail_Message */
    public function setTo($email, $name = null){return $this->addEmail('to', $email, $name, true);}  
    /** @return T3Mail_Message */
    public function addToArray(array $array){return $this->addEmailsArray('to', $array);} 
    /** @return T3Mail_Message */    
    public function setToArray(array $array){return $this->addEmailsArray('to', $array, true);}  
    /** @return T3Mail_Message */
    public function addToString($string){return $this->addEmailsString('to', $string);}    
    /** @return T3Mail_Message */
    public function setToString($string){return $this->addEmailsString('to', $string, true);}  
    /* ------- */ 
    
    /** --- CC --- **/                                                                       
    /** @return T3Mail_Message */
    public function addCc($email, $name = null){return $this->addEmail('cc', $email, $name);}   
    /** @return T3Mail_Message */  
    public function setCc($email, $name = null){return $this->addEmail('cc', $email, $name, true);}   
    /** @return T3Mail_Message */  
    public function addCcArray(array $array){return $this->addEmailsArray('cc', $array);}   
    /** @return T3Mail_Message */  
    public function setCcArray(array $array){return $this->addEmailsArray('cc', $array, true);}  
    /** @return T3Mail_Message */
    public function addCcString($string){return $this->addEmailsString('cc', $string);}
    /** @return T3Mail_Message */
    public function setCcString($string){return $this->addEmailsString('cc', $string, true);}
    /* ------- */ 
    
    /** --- BCC --- **/                                                  
    /** @return T3Mail_Message */
    public function addBcc($email){return $this->addEmail('bcc', $email);}   
    /** @return T3Mail_Message */
    public function setBcc($email){return $this->addEmail('bcc', $email, null, true); }   
    /** @return T3Mail_Message */
    public function addBccArray(array $array){return $this->addEmailsArray('bcc', $array);}   
    /** @return T3Mail_Message */
    public function setBccArray(array $array){return $this->addEmailsArray('bcc', $array, true);} 
    /** @return T3Mail_Message */
    public function addBccString($string){return $this->addEmailsString('bcc', $string);}
    /** @return T3Mail_Message */ 
    public function setBccString($string){return $this->addEmailsString('bcc', $string, true);}
    /* ------- */
    
    
    public function SendMail($email = null, $name = null){
        if($email){
            $this->to = array();
            $this->addTo($email, $name);
        }
        
        $messageText = $this->renderLast($this->messageText);
        
        $mail = new Zend_Mail('utf-8');

        $mail->setFrom($this->fromEmail, $this->fromName);
        
        if(is_array($this->to) && count($this->to)){
            foreach($this->to as $el){
                $mail->addTo($el['email'], '=?utf-8?B?'.base64_encode($el['name']).'?=');
            }
        }
        
        if(is_array($this->cc) && count($this->cc)){
            foreach($this->cc as $el){
                $mail->addCc($el['email'], '=?utf-8?B?'.base64_encode($el['name']).'?=');
            }
        }
        
        if(is_array($this->bcc) && count($this->bcc)){
            foreach($this->bcc as $el){
                $mail->addBcc($el['email']);        
            }
        }
        
        
        
        if(is_array($this->attachments) && count($this->attachments)){
            foreach($this->attachments as $file){
                
                $mail->createAttachment(
                    file_get_contents($file),
                    null,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    basename($file) 
                );    
            }    
        }
        
        $mail->setSubject('=?utf-8?B?'.base64_encode($this->subject).'?='); 
        $mail->setBodyHtml($messageText, 'utf-8' /* , Zend_Mime::ENCODING_BASE64 */);
        //varExport($this->subject);
        
        
        return $this->_sendNotStatic($mail);
          
    }
    
    /**
    * ????????? ?????? ????????????
    * 
    * @param mixed $user
    */
    public function SendMail_For_User($user){
        
        if(is_numeric($user)){
            $userID = $user;
            $user = new T3User();
            $user->fromDatabase($userID);    
        }
        else if(is_string($user)){
            if(Zend_Validate::is($user , "T3_NewLogin", array(false), "AZend_Validate")){  
                $userLogin = $user;
                $user = new T3User();
                $user->fromDatabase(array('login' => $userLogin));
            }    
        }
             
        
        if(!(is_object($user) && $user instanceof T3User && $user->id && $user->email)){
            return false;    
        }
        
        if($user->subscribe == '0'){
            return false;    
        }

        // Если пользователь забанен (ак же это бывает если у вебмастра статус: lock or hold), ему ничего не помылать!
        if($user->ban){
            return false;
        }
        
        if(!T3Mail_UnsubscribeGroups::isPost($user, $this->templateMessage)){
            return false; 
        }  

        $this->setTo($user->email, $user->first_name . " " . $user->last_name);
        $this->SendMail();
        /*
        $messageText = self::renderText_From_User($this->messageText, $user);
        
        $messageText = $this->renderLast($messageText);
        
        $mail = new Zend_Mail('utf-8');  
        
        $mail->setBodyHtml($messageText);
        $mail->setFrom($this->fromEmail, $this->fromName);
        
        $mail->addTo($user->email, $user->nickname);
        $this->setTo($user->email, $user->nickname);        
        
        $mail->setSubject('=?utf-8?B?'.base64_encode($this->subject).'?=');
        
        if(is_array($this->attachments) && count($this->attachments)){
            foreach($this->attachments as $file){
                
                $mail->createAttachment(
                    file_get_contents($file),
                    null,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    basename($file) 
                );    
            }    
        }
        
        // senea 18.10.2011
		if(is_array($this->cc) && count($this->cc)){
            foreach($this->cc as $el){
                $mail->addCc($el['email'], $el['name']);        
            }
        }
        
        if(is_array($this->bcc) && count($this->bcc)){
            foreach($this->bcc as $el){
                $mail->addBcc($el['email']);        
            }
        }
        //
        //varDump2($mail);
        
        return $this->_sendNotStatic($mail);
        */
    }

    protected $mx_server   = 'smtp.t3leads.com';
    protected $mx_username = 't3leads@t3leads.com';
    protected $mx_password = '4iVZReL2qLF@dCuh';

    public function setMx(
        $server = 'smtp.t3leads.com',
        $username = 't3leads@t3leads.com',
        $password = '4iVZReL2qLF@dCuh'
    ){
        $this->mx_server = $server;
        $this->mx_username = $username;
        $this->mx_password = $password;
    }

    protected function _sendNotStatic(Zend_Mail $mail){
        if(T3Mail_Message::$debug){
            varExport($this->to);
            varExport($this->getUnsubscribeURL());
            echo $this->messageText;
            die;
        }
        
        // Если сообщение имеет ID (оно не тестовое)
        if($this->id){
            T3Mail_Message_Send::createNew()->setParams(array(
                'messageID' => $this->id, 
                'sendDate'  => date("Y-m-d H:i:s"),
                'subject'   => $this->subject,   
                'from'      => "{$this->fromName} <{$this->fromEmail}>",
                'to'        => $this->to, 
                'cc'        => $this->cc,
                'bcc'       => $this->bcc,
            ))->insertIntoDatabase();
        } 
        
        return $this->_send($mail, $this->mx_server, $this->mx_username, $this->mx_password);
    }
    
    static protected $isSendError = false;
    
    static public function _send(
        Zend_Mail $mail,
        $server = 'smtp.t3leads.com',
        $username = 't3leads@t3leads.com',
        $password = '4iVZReL2qLF@dCuh'
    ){
        $mail->addHeader("MIME-Version", "1.0");
        $result = false;
        try {
            $transport = new Zend_Mail_Transport_Smtp($server, array(
                'auth' => 'login',
                'username' => $username,
                'password' => $password
            ));

            $result = $mail->send($transport);
        }
        catch(Exception $e){
            $s = $mail->getSubject();
            if(substr($s, 0, 10) == "=?utf-8?B?"){
                $s = base64_decode(substr($s, 10, strlen($s) - 12));
            }

            T3Db::api()->insert("mail_errors", array(
                'subject'   => $s,
                'headers'   => var_export($mail->getHeaders(), 1),
                'error'     => $e->getMessage() . "\r\n\r\n" . $e->getTraceAsString(),
            ));

            // не удалось отправить письмо
            try {  
                if(!self::$isSendError){
                    self::$isSendError = true;
                    
                    T3FatalMessage::sendMessage("SMTP_error", array(
                        'transport' => var_export($transport, 1),
                        'server' => var_export($_SERVER, 1),
                    ));
                } 
            }
            catch(Exception $e){} 
        }
        
        return $result;  
    }
    
    public function SendMail_For_Company($company){
        
        if(is_numeric($company)){
            $companyID = $company;    
        }
        else if(is_object($company) && $company instanceof T3CompanyInterface && $company->id){
            $companyID = $company->id;
        }
        else {
            return null;    
        }

        $users = T3System::getConnect()->fetchCol("select id from users where company_id=?", $companyID);

        foreach($users as $user){
            $this->SendMail_For_User($user);    
        }     
    }
    
    protected function renderText_From_User($text, T3User $user){
        $params = $user->getParams();
        $params['dl_sync'] = "https://account.destinationleads.com/create-account-affiliate?sync-t3leads-key=" .
            $user->id . "-" . $user->getWebmasterSyncKey();
        
        if(isset($params) && is_array($params) && count($params)){
            foreach($params as $name => $value){
                if(is_string($value) || is_null($value)){
                    $text = str_replace('{user:' . $name . '}', $value, $text);
                }    
            }    
        } 
                        
        
        return $text; 
    }
    
    /**
    * ????????? ????????? ??????
    * 1. ?????? ?? unsubscribe
    * 2. ????????? ?? ?????
    * 
    * @param string $messageText
    * @return string
    */
    protected function renderLast($messageText){
        $messageText = str_replace(array(
            "{system:unsubscribeLink}",
            "{system:domainAndProtocol}",
            "{system:websiteURL}",
        ),array(
            $this->getUnsubscribeURL(),
            $this->getDomainAndProtocol(),
            $this->getVisitWebsiteURL(),   
        ) , $messageText);   
        
        if(strpos($messageText, "{system:showLink}") !== false){
            $key = md5(rand(10000000, 99999999));
            T3System::getConnect()->insert('mail_show', array('key' => $key));
            
            $showID = T3System::getConnect()->lastInsertId();
            
            $messageText = str_replace("{system:showLink}", $this->getShowEmailURL($showID, $key), $messageText);
            T3System::getConnect()->update('mail_show', array('text' => $messageText), "id={$showID}");
        }
        
        return $messageText; 
    }
    
    protected function getShowEmailURL($id, $key){
        return self::getDomainAndProtocol() . str_replace(array("{id}", "{key}"), array(IdEncryptor::encode($id), $key), self::showPageURL);
    }
    
    protected function getVisitWebsiteURL(){
        return self::getDomainAndProtocol() . self::visitWebsiteURL;
    }
    
    protected function getDomainAndProtocol(){
        if(ifset($_SERVER['HTTP_HOST'])){
            $domain = $_SERVER['HTTP_HOST'];      
        }
        else {
            $domain = self::defaultDomain;     
        }
        return "https://" . $domain;    
    }
    
    protected function getUnsubscribeURL(){
        if($this->templateMessage){
            $UnsubscribeGroup = $this->templateMessage->getUnsubscribeGroup();
            if($UnsubscribeGroup){
                return self::getDomainAndProtocol() . self::unsubscribeURL . "?type=" . $UnsubscribeGroup->name;
            }
            else {
                return self::getDomainAndProtocol() . self::unsubscribeURL . "?error=GroupNotFound";        
            }
        }
        else {
            return self::getDomainAndProtocol() . self::unsubscribeURL . "?error=TestMode";     
        }
    }
}