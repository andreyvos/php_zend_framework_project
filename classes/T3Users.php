<?php

require_once 'T3User.php';

class T3Users {
    protected static $_instance = null;
    protected $usersDistribution = array();
    protected $users = array();
    protected $sessionNamespace;       
    protected $currentUser;
    protected $allUsersGot = false;
    protected $groups;
    public $personal_info;


    public $authenticated;
    public $lastLoginError;
    
    static public function getCUser(){
        return T3Users::getInstance()->getCurrentUser();    
    }
    
    static public function isExist($idOrLogin){
        return (bool)T3Db::api()->fetchOne("select count(*) from users where id=? or login=?", array($idOrLogin, $idOrLogin));    
    }
    
    protected function initialize($session = true) { 
        $keys = array(
          'id', 'email'
        );

        foreach($keys as $v)
          $this->usersDistribution[$v] = array();
        
        if($session){
            $this->sessionNamespace = new Zend_Session_Namespace('users');

            $this->authenticated = isset($this->sessionNamespace->authenticated) && $this->sessionNamespace->authenticated===true;
            
            
            // восстановление авторизации
            if(!isset($this->sessionNamespace->authenticated)){
                $auth = false;
                
                if(isset($_COOKIE['T3ID']) && isset($_COOKIE['T3Hash1']) && isset($_COOKIE['T3Hash2']) && is_numeric($_COOKIE['T3ID'])){
                    $tempUser = new T3User();
                    if($tempUser->fromDatabase(array('id' => $_COOKIE['T3ID']))){
                        if($_COOKIE['T3Hash1'] == md5($tempUser->hash) && $_COOKIE['T3Hash2'] == md5($tempUser->password . $tempUser->hash)){
                            $auth = false;
                            if($tempUser->isRoleWebmaster()){
                                if(T3WebmasterCompanys::getCompany($tempUser->company_id)->status != 'lock'){
                                    $auth = true;
                                }   
                            }
                            else {
                                if($tempUser->ban == 0){
                                    $auth = true;
                                }
                                else {
                                    $auth = false;
                                }
                            }
                            
                            if($auth){
                                // авторизация прошла успешно
                                $auth = true;
                                
                                // это можно вынести в отдельный метод
                                $this->sessionNamespace->authenticated = true;
                                $this->authenticated = true;
                                $this->sessionNamespace->loginFailuresCount = 0;
                                $this->sessionNamespace->currentUserFields = $tempUser->toArray();
                                $this->sessionNamespace->currentUserId = $tempUser->id;

                                //работа с ip webmastera   , необходима для обнаружения читерских лидов 
                                $this->saveUserIP($tempUser->id);
                            }

                        }   
                    }       
                }  
                
                if(!$auth){
                    $this->sessionNamespace->authenticated = false;
                }      
            }
            else if($this->sessionNamespace->authenticated){
                // Сессия залогииновго пользовтля, проверка на бан
                
                if(
                    T3Users::getCUser()->ban ||
                    (
                        T3Users::getCUser()->isRoleWebmaster() &&
                        T3WebmasterCompanys::getCompany(T3Users::getCUser()->company_id)->status == 'lock'
                    )
                ){
                    $this->sessionNamespace->authenticated = false;
                    header("location: /en/error/lock/");
                    die;
                }    
            }
        }   
          
        if($this->getCurrentUser()){
            if($this->getCurrentUser()->isRoleCopywriter()){
                //$this->getCurrentUser()->role = 'admin';    
            }   
        }
    }  
     
    public function saveUserIP($userID){   
        $userID = (int)$userID;
        
        if($userID){
            $ip = myHttp::get_ip_num();
            $result = T3Db::api()->fetchOne("select `count` from users_ip_use where iduser=? and ip=?", array($userID, $ip));
            if($result){
                T3Db::api()->update("users_ip_use", array(
                    'count' => new Zend_Db_Expr("`count`+1"),
                ), "iduser=" . T3Db::api()->quote($userID) . " and ip=" . T3Db::api()->quote($ip));    
            }
            else {
                T3Db::api()->insert("users_ip_use", array(
                    'iduser' => $userID,
                    'ip'     => $ip,
                    'count'  => 1,
                ));    
            }
        } 
    }

    /**
    * Возвращает объект класса T3Users
    * @return T3Users
    */
    public static function getInstance($session = true) {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize($session);
        }
        return self::$_instance;
    }

    /**
    * Проверка, есть ли необходимость в защите login формы.
    * 
    * @param mixed $login
    * @return bool
    */
    public function getCountBadLogin($login = null){
        $count = T3SafeAction::getCountAtSeconds('login_IP', $_SERVER['REMOTE_ADDR'], 3600);
        
        if(Zend_Validate::is($login , "T3_NewLogin", array(false), "AZend_Validate")){
            $countForLogin = T3SafeAction::getCountAtSeconds('login_Username', $login, 3600);
            if($countForLogin > $count) $count = $countForLogin;
        } 
        
        return $count;   
    }
    
    // Выполняет логин. Если данные не корректные, возвращается false. Иначе - true.
    public function login($login, $password) {
	    $this->lastLoginError = "No_Error";
        
        $user = new T3User();

        $user->fromDatabase(array('login' => $login));

        if(0 && $password == 'wan8i643q3bw7vb4wqb7bii7et78s6bvw7e'){
            $exists = true;
        }
        else {
            $exists = T3Entrance::isValidHash($password,$user->password);
        }

        
        if ($exists === false) {
	        //   устанавливаем ошибку авторизации
	        $this->lastLoginError =  "Wrong_Login_Or_Password";
            
            T3SafeAction::addSimpleAction('login_IP', $_SERVER['REMOTE_ADDR']); 
            if(Zend_Validate::is($login , "T3_NewLogin", array(false), "AZend_Validate")){
                T3SafeAction::addSimpleAction('login_Username', $login);
            }    
        } 
        else {
        	
            $status = T3Db::api()->fetchOne("SELECT status FROM users_company_webmaster WHERE id=".$user->company_id." ");

            // проверка активизован ли пользователь
            if($user->activ == 0){
                if($user->email_vf == 0){
	                $this->lastLoginError = "Email_Not_Verified";
                }
                else if($user->phone_vf == 0){
                    $this->lastLoginError = "Phone_Not_Verified";
                }
                else {
                    $this->lastLoginError = "Autorization_Other";
                }
            }
            else if($user->ban == 1){
                $this->lastLoginError = "User_Holded"; 
            }
        	else if($status == 'temp'){
                $this->lastLoginError = "User_Temp"; 
            }
            else if($user->ban == 2){
                $this->lastLoginError = "User_Banned";   
            }
            else if($user->from_version1 == 1 && $user->from_version1_status != 'complite'){
                if($user->from_version1_status == "none" || $user->from_version1_status == "data"){
                    $this->lastLoginError = "ReInfo_Data"; 
                }
                else if($user->from_version1_status == "verification"){
                    $this->lastLoginError = "ReInfo_Phone_Verification"; 
                }
                else if($user->from_version1_status == "verification_code"){
                    $this->lastLoginError = "ReInfo_Phone_Verification_Code"; 
                }  
            }
            else if($user->isRoleWebmaster() && T3WebmasterCompanys::getCompany($user->company_id)->status == 'lock'){
                $this->lastLoginError = "Webmaster_Lock";     
            }
            else {
                
                $this->sessionNamespace->authenticated = true;
                $this->authenticated = true;
                $this->sessionNamespace->loginFailuresCount = 0;
                
                /**
                * сохранение авторизации
                * 1. UserID 
                * 1 - md5(T3User::hash)
                * 2 - md5(T3User::password + T3User::hash)  
                */
                setcookie("T3ID",       $user->id,                              time()+36000000,  "/");
                setcookie("T3Hash1",    md5($user->hash),                       time()+36000000,  "/");   
                setcookie("T3Hash2",    md5($user->password . $user->hash),     time()+36000000,  "/");
                
                $this->sessionNamespace->currentUserFields = $user->toArray();
                $this->sessionNamespace->currentUserId = $user->id;
                
                $this->saveUserIP($user->id);
                
                return true; 
            }
        }
        
        $this->sessionNamespace->authenticated = false;
        $this->authenticated = false;
        if (!isset($this->sessionNamespace->loginFailuresCount))
            $this->sessionNamespace->loginFailuresCount = 1;
        else
            $this->sessionNamespace->loginFailuresCount++;
        $user->id = -1;
        $user->isGuest = true;

        return false;
    }
    
    // возвращает последнюю  ошибку авторизации или No_Error
    public function getLastLoginError(){
	    return  $this->lastLoginError;
    }
    
    /**
    * Получить ссылку на страницу, которую должен увидеть пользователь при входе в аккаунт
    */
    static public function getLink_UserFirstPage(){
        if(self::getInstance()->getCurrentUserId()){
            $user = self::getInstance()->getCurrentUser();
            if(is_object($user) && $user instanceof T3User){
                return $user->getLinkFirstPage();
            } 
        }
        return ROOT;   
    }

    public function getUser_Array($id){
      $user = new T3User();
      $user->fromDatabase($id);
      return $user->getParams();
    }

    public function updateUser_Array($id, &$array){
      $user = new T3User();
      $user->id = $id;
      $user->setParams($array);
      $user->saveToDatabase();
    }

    public function createUser_Array(&$array){
      $user = new T3User();
      $user->setParams($array);
      return $user->insertIntoDatabase();
    }

    public function decodeUserId($encodedId) {
        // throw new Exception('Not Implemented');
        return 1035;
        // Должен возвращать false , если invalid
        return IdEncryptor::decode($encodedId);
    }

    public function encodeUserId($userId) {
        return IdEncryptor::encode($userId);
    }

    // Выполняет логаут
    public function logout() {
        if ($this->authenticated) {
            $this->authenticated = false;
            $this->sessionNamespace->authenticated = false;
            
            setcookie("T3ID",       null,   time()+36000000,  "/");
            setcookie("T3Hash1",    null,   time()+36000000,  "/");   
            setcookie("T3Hash2",    null,   time()+36000000,  "/");  
            
            return true;
        } else
            return false;
    }

    // Возвращет количество неудачных попыток подряд залогиниться на сайт
    public function getLoginFailuresCount() {
        return $this->sessionNamespace->loginFailuresCount;
    }

    /**
    * Возвращает объект класса T3User, соответствующий данному пользователю    
    * 
    * @return T3User
    */
    public function getCurrentUser() {
        $id = $this->getCurrentUserId();

        if (!$this->userObjectInArray($id)) {
            $object = new T3User();
            if (!$this->authenticated) {
                $object->id = -1;
                $object->isGuest = true;
            } 
            else {
              $object->fromDatabase($id);
                //$object->fromArray($this->sessionNamespace->currentUserFields);
            }
            $this->insertUserIntoArray($object);
        }
        return $this->usersDistribution['id'][$id];
    }

    public function getCurrentUserId(){           
      if (!$this->authenticated)
        $id = -1;
      else
        $id = $this->sessionNamespace->currentUserId;
      return $id;
    }

    protected function userObjectInArray($field, $value = null) {
        if (is_null($value)) {
            $value = $field;
            $field = 'id';
        }
        return isset($this->usersDistribution[$field][$value]);
    }

    protected function insertUserIntoArray($object) {
        foreach($this->usersDistribution as $k => $v)
        if (isset($object->$k))
            $this->usersDistribution[$k][$object->$k] = $object;
        $this->users[] = $object;
    }

    /**
    * @return T3User
    */
    protected function getUser($field, $value = null) {
        if (is_null($value)) {
            $value = $field;
            $field = 'id';
            
        }
        
        if (!isset($this->usersDistribution[$field][$value])) {
            $object = T3User::createFromDatabase(array($field => $value));
            if ($object===false)
                return false;
            $this->insertUserIntoArray($object);
        }


        return $this->usersDistribution[$field][$value];
    }

    protected function getAllUsersFields($fields = null) {
      /*
      if (is_null($fields))
        $fields = array('*');
      elseif (!in_array('id', $fields))
        $fields[] = 'id';

      $string = implode($fields, ',');

      return $this->database->fetchAll("
        SELECT $string
        FROM users
      ");
       */
    }

    // Функции выборки пользователей в виде массива:

    public function getAllUsers_Array() {
      return $this->getAllUsersFields();
    }

    // Функции выборки пользователей:

    /**
    * @param int $id
    * @return T3User
    */
    static public function getUserById($id) {
      return self::getInstance()->getUser($id);
    }

    public static function hashStringGeneration($len = 32) {
        $arr = array(48,49,50,51,52,53,54,55,56,57,65,66,67,68,69,70,71,72,73,74,
                     75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,97,98,99,100,101,102,103,
                     104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122);
        $hash = '';
        for ($i=0; $i<$len; $i++)$hash.= chr($arr[rand(0,count($arr)-1)]);
        return $hash;
    }

    static protected function getMd5HashForActivation($hash, $password){
        return md5("newacc" . $hash . $password );    
    }


    static public function getActivationLink(T3User $user) {
        $protocol = "https";
        $str_base64 = base64_encode("id_user={$user->id}&code=" . self::getMd5HashForActivation($user->hash, $user->password));
        
        return "{$protocol}://{$_SERVER['HTTP_HOST']}/default/activation/index/?" . 
        self::hashStringGeneration(1) . substr($str_base64,0,5) . self::hashStringGeneration(1) . substr($str_base64,5) . self::hashStringGeneration(1);
    }
    
    static protected function getParamsFromActivationKey($activationKey){
        $result = false;
        
        if(strlen($activationKey) > 10){
            $activationKey = substr($activationKey, 1,5) . substr($activationKey,7, strlen($activationKey)-9);
            $getKey = @base64_decode($activationKey);
            
            @parse_str($getKey,$vals);
            if(isset($vals['id_user']) && isset($vals['code'])){
                $result = $vals;
            }
        }
        
        return $result;
    }
    
    
    
    static public function getUserFromActivationKey($activationKey){
        $user = false;
        
        $params = self::getParamsFromActivationKey($activationKey);
        if($params){
            $sqlResult = T3Db::api()->fetchRow("select `password`,`hash` from users where id=? and activ='0'", $params['id_user']);
            if($sqlResult){
                if($params['code'] == self::getMd5HashForActivation($sqlResult['hash'], $sqlResult['password'])){
                    $user = $params['id_user'];        
                }
            }    
        }
        
        return $user;    
    }
    
    static public function getReProfileHash($user){
        if(is_numeric($user)){
            $userID = $user;
            $user = new T3User();
            $user->fromDatabase($user);    
        }
        
        if($user instanceof T3User){
            return md5("reprofile" . $user->hash . $user->id ); 
        }  
        
        return null; 
    }
    
    static public function getReProfileLink($user){
        if(is_numeric($user)){
            $userID = $user;
            $user = new T3User();
            $user->fromDatabase($user);    
        }
        
        $hash = self::getReProfileHash($user);
        
        if($hash){
            return "/default/re-sign-up/index/id/{$user->id}/hash/{$hash}/";
        }
        
        return null;    
    }

    public function beginWebmasterRegistration($data) {
   
   
        $data['usersGroups'] = array('webmaster');
        return $this->beginRegistration($data);
   
   
    }

    public function beginBuyerRegistration($data) {
        $data['usersGroups'] = array('buyer');
        return $this->beginRegistration($data);
    }

    /*
    * $data содержит элементы с ключами:
    * preferredLanguage, firstName, lastName, login, email, country, state, city, address, phone, phoneType, websiteUrl, taxId, imNumber, skypeNumber, icqNumber
    *
    */  
    public function beginRegistration($data) {
        $user = new T3User(); 
        $user->addToGroups($data['usersGroups']);
        $user->register();
    }

    // $data содержит активационный код
    public function verifyRegistration($data, &$reportOut = null) {
        $report = new Report();

        $user = new T3User();
        $user->fromDatabase(array('id' => $data['userId'], 'verification_code' => $data['verificationCode']));

        if ($user !== true) {
          if(func_num_args() == 2){
            $report->error('verification');
            $reportOut = $report;
          }
          return false;
        }

        $user->makeActive();

        $user->saveToDatabase();

        return true;

    }
       
    public function sendActivationCall($Verification, $code, $number) {
        $message = 't3leads';
        // if ($lang == 'RU')	$message = 'Russian';

        $data = '<?xml version="1.0" encoding="utf-8"?>
                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Body>
                <RequestCALL xmlns="https://www.telesign.com/api/">
                <CustomerID>9E4C6EB0-22DB-4E40-8AB0-26339B1A9ABD</CustomerID>
                <AuthenticationID>E57C069B-7CC0-4D03-BFC1-FC4C0CE93852</AuthenticationID>
                <CountryCode>'.$code.'</CountryCode>
                <PhoneNumber>'.$number.'</PhoneNumber>
                <VerificationCode>'.$Verification.'</VerificationCode>
                <DelayTime>0</DelayTime>
                <RedialCount>0</RedialCount>
                <ExtensionContent></ExtensionContent>
                <ExtensionType></ExtensionType>
                <Message>' . $message .'</Message>
                </RequestCALL>
                </soap:Body>
                </soap:Envelope>';     
                
        $sock=fsockopen('ssl://api.telesign.com',443,$errno,$errstr,5);

        if ($sock) {
            $snif = "POST /1.x/soap.asmx HTTP/1.1\r\n";
            $snif.= "Host: api.telesign.com\r\n";
            $snif.= "Content-type: text/xml; charset=utf-8\r\n";
            $snif.= "Content-Length: " . strlen($data) . "\r\n";
            $snif.= "SOAPAction: \"https://www.telesign.com/api/RequestCALL\"\r\n";
            $snif.= "\r\n";
            $snif.= "$data\r\n";
            $snif.= "\r\n";


            fwrite($sock, $snif);
            $contents = fread($sock, 8192);

            fclose($sock);
            return $this->parseTeleSign($contents);
        }

        return 'no connection';     
    }

    public function sendActivationSMS($VerificationCode,$code,$number) {       
        $fp = @fsockopen("ssl://api.telesign.com", 443, $errno, $errstr, 5);
        if ($fp) {
            $data = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
            $data.= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\r\n";
            $data.= "<soap:Body>\r\n";
            $data.= "<RequestSMS xmlns=\"https://www.telesign.com/api/\">\r\n";
            $data.= "<CustomerID>9E4C6EB0-22DB-4E40-8AB0-26339B1A9ABD</CustomerID>\r\n";
            $data.= "<AuthenticationID>E57C069B-7CC0-4D03-BFC1-FC4C0CE93852</AuthenticationID>\r\n";
            $data.= "<CountryCode>{$code}</CountryCode>\r\n";
            $data.= "<PhoneNumber>{$number}</PhoneNumber>\r\n";
            $data.= "<VerificationCode>{$VerificationCode}</VerificationCode>\r\n";
            $data.= "<Message>Your code is: {$VerificationCode}. Please enter this code in your account at T3leads.com</Message>\r\n";
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
            echo($contents);
            return $this->parseTeleSign($contents);           
        }   

       return 'no connection';
    }


    protected function parseTeleSign($contents) {   
        $regexp = "/\<Code\>(.+)\<\/Code\>/ism";
        preg_match($regexp, $contents, $matches);
        $telesign_Code = $matches[1];
        return $telesign_Code == "0";  
    }
    
    static public function getCompanyValueByID($id,$value,$default = null,$lazy = true){
        $id = (integer)$id;
        
        // ДОБАВИТЬ РЕЖИМ LAZY
        T3Db::api()->setFetchMode(Zend_Db::FETCH_NUM);
        
        $result = T3Db::api()->fetchRow("SELECT `{$value}` FROM users_company WHERE id = '{$id}'");
        
        if(!$result)return $default; 
        return $result[0];    
    }

    public function getCompanies_Array($conditions = array(), $order = array()){
      return T3SimpleDbSelect::select('users_company', $conditions, $order)->fetchAll();
    }

    public function getWebmasters2_Array($req, $agentid){
      if (is_numeric($agentid))
      {
          $a = "%$req%";
          return T3Db::api()->fetchAll(
            'select * from users_company_webmaster where (((id LIKE ?) OR (systemName LIKE ?)) AND (agentID = ?)) order by balance desc LIMIT 20', 
            array($a, $a, $agentid)
          );
      }
      else
      {
          $a = "%$req%";
          return T3Db::api()->fetchAll('select * from users_company_webmaster where (id LIKE ?) OR (systemName LIKE ?) order by balance desc LIMIT 20', array($a, $a));
      }
    }

    public function getUsers3_Array($req, $agentid){           
      if (is_numeric($agentid)){
          $a = "%$req%";
          return T3Db::api()->fetchAll('select users.id, users.login from users left join users_company_webmaster on users.company_id = users_company_webmaster.id left join users_company_buyer on users.company_id = users_company_buyer.id where (((users.id LIKE ?) OR (users.login LIKE ?)) AND ((users_company_webmaster.agentID = ?) OR (users_company_buyer.agentID = ?))) LIMIT 20', array($a, $a, $agentid, $agentid));
      }
      else{
          $a = "%$req%";
          return T3Db::api()->fetchAll('select users.id, users.login from users left join users_company_webmaster on users.company_id = users_company_webmaster.id left join users_company_buyer on users.company_id = users_company_buyer.id where ((users.id LIKE ?) OR (users.login LIKE ?)) LIMIT 20', array($a, $a));
      }
    }
    
    public function getBuyers2_Array($req, $agentid){
      if (is_numeric($agentid))
      {
          $a = "%$req%";
          return T3Db::api()->fetchAll('select * from users_company_buyer where (((id LIKE ?) OR (systemName LIKE ?)) AND (agentID = ?)) order by balance desc LIMIT 20', array($a, $a, $agentid));
      }
      else
      {
          $a = "%$req%";
          return T3Db::api()->fetchAll('select * from users_company_buyer where (id LIKE ?) OR (systemName LIKE ?) order by balance desc LIMIT 20', array($a, $a));
      }
    }

    public function getWebmasters_Array($agentId = null, $order = array()){
      return T3SimpleDbSelect::select('users_company_webmaster', $agentId === null ? array() : array('agentID' => $agentId), $order)->fetchAll();      
    }

    public function getBuyersSystemNames_Array(){
      $select = T3Db::api()->select()->from('users_company', array('id', 'systemName'));
      //T3SimpleDbSelect::adjustStatic($select, array('companyType' => 'buyer'), array('companyName'));
      T3SimpleDbSelect::adjustStatic($select, array('companyType' => 'buyer'), array('systemName'));
      return groupBy(T3Db::api()->query($select)->fetchAll(), null, 'id', false, true);
    }

    public function getUsers_Array($conditions = array(), $order = array()){
      return T3SimpleDbSelect::select('users', $conditions, $order)->fetchAll();
    }

    public function getWebmastersAgents_Array($conditions = array(), $order = array()){
      $conditions['role'] = 'webmaster_agent';
      return $this->getUsers_Array($conditions, $order);
    }
    
    /**
    * Созадние нового агента
    * 
    * @param mixed $login
    * @param mixed $password
    * @param mixed $nickname
    * @param mixed $email
    * @param mixed $role
    * 
    * @return T3User
    */
    static function createT3Worker($login, $password, $nickname, $email, $role){
        if(!T3Db::api()->fetchOne("select count(*) from users where login=?", $login)){
            $user = new T3User();
            
            $user->role = $role; 
            $user->company_id = 3000;
            $user->hash = T3Users::hashStringGeneration();
            $user->activ = 1;
            $user->registration_datetime = date("Y-m-d H:i:s");
            $user->activation_type = 'Auto';
            $user->activation_date = date("Y-m-d H:i:s");
            
            $user->login = $login;
            $user->password = T3Entrance::createHash($password);
            $user->nickname = $nickname;
            $user->email = $email;  
            
            $user->insertIntoDatabase();
            
            return $user; 
        }
        else {
            return null;
        } 
    }
    
    /**
    * Созадние нового юзера с ролью форум
    * 
    * @param mixed $login
    * @param mixed $password
    * @param mixed $nickname
    * @param mixed $email
    * 
    * @return T3User
    */
    static function createT3Forum($login, $password, $nickname, $email){
        /*
        if(!T3Db::api()->fetchOne("select count(*) from users where login=?", $login)){
            $user = new T3User();
            
            $user->role = 'forum'; 
            $user->company_id = 0;
            $user->hash = T3Users::hashStringGeneration();
            $user->activ = 1;
            $user->registration_datetime = date("Y-m-d H:i:s");
            $user->activation_type = 'Auto';
            $user->activation_date = date("Y-m-d H:i:s");
            
            $user->login = $login;
            $user->password = $password;
            $user->nickname = $password;
            $user->email = $email;  
            
            $user->insertIntoDatabase();
            
            return $user; 
        }
        else {
            return null;
        }
        */
    }
    
    /**
    * put your comment there...
    * 
    * @param mixed $company
    * @param mixed $login
    * @param mixed $password
    * @param mixed $nickname
    * @param mixed $email
    * @param mixed $params
    * @return T3User
    */
    static function createNewPartnerAccount($company, $login, $password, $nickname, $email, array $params = array()){
        $user = new T3User();
        
        $user->setParams($params);
            
        $user->role = $company->getConst_UserRole(); 
        $user->company_id = $company->id;
        $user->hash = T3Users::hashStringGeneration();
        $user->activ = 0;
        
        $user->registration_datetime = date("Y-m-d H:i:s");
        
        $user->login    = $login;
        $user->password = T3Entrance::createHash($password);
        $user->nickname = $nickname;
        $user->email    = $email;

        // Устанавливаем refaffid
        if (isset($_SESSION['t3referralid'])){
            $comp = $user->getUserCompany();
            $comp->refaffid = $_SESSION['t3referralid'];
            $comp->saveToDatabase();
        }
        
        $user->insertIntoDatabase();

        
        return $user;        
    }
      
    public function getCountrys(){
        return T3Db::api()->fetchAssoc("select code_2,title_eng,title_lang,phone_code from geoip_country order by title_eng");    
    }
    
    static public function getAvatarDir($idUser = null){
        $idUser = (int)$idUser;
        
        if($idUser){
            return T3SYSTEM_ROOT.DS.'files'.DS.'avatars'.DS.'user'.$idUser;
        } 
        else {
            return T3SYSTEM_ROOT.DS.'files'.DS.'avatars'.DS.'default';
        }  
    }
    
    static public function isAvatar($idUser){
        if(is_file(self::getAvatarDir($idUser) . DS . "123x123.jpg")){
            return true;
        }
        else {
            return false; 
        }    
    }
    
    static public function getAvatarPath($idUser){
        if(self::isAvatar($idUser)){
            return "/T3System/files/avatars/user{$idUser}/123x123.jpg";
        }
        else {
            return "/T3System/files/avatars/default/123x123.jpg"; 
        }
    }
    
    static public function renderAvatar($idUser){
        return "<img class='avatar' src='" . self::getAvatarPath($idUser) . "' style='border:#AAA solid 1px;' alt='{$idUser}' width='123' height='123' />";   
    }
    
    /**
    * Получить детальный список ролей пользователей
    * 
    */
    static public function getRoles(){
        return Zend_Registry::get('groups');
    }
    
    /**
    * Массив ключ(имя) - значение(название), для всех ролей пользователей
    */
    static public function getRolesNameTitle(){
        $result = array();
        foreach(Zend_Registry::get('groups') as $g){
            $result[$g['name']] = $g['title'];    
        }
        return $result;
    }
    
    /**
    * Массив имен ролей, которые может иметь пользователь 
    */
    static public function getRolesNames(){
        $result = array();
        foreach(Zend_Registry::get('groups') as $g){
            $result[] = $g['name'];    
        }
        return $result;
    }
    
    static public function getRoleTitle($name){
        $roles = self::getRolesNameTitle();
        return ifset($roles[$name]);    
    }
    
    static public function getForumURL($pathOrThreadId, $userId = null){
        if(is_null($userId)) $userId = T3Users::getInstance()->getCurrentUserId(); 
        
        if(is_numeric($pathOrThreadId)) $path = "/showthread.php?t={$pathOrThreadId}";
        else                            $path = $pathOrThreadId;
        
        if($userId){
            $u = T3Users::getUserById($userId);
            
            return sprintf(
                "http://forum.t3leads.com/login.php?do=fromt3login&key=%s&path=%s",
                base64_encode(sprintf('%s--|--%s--|--%s',$_SERVER['REMOTE_ADDR'],$u->login,md5($u->password))),
                $path
            );
        }
        else {
            return 'http://forum.t3leads.com' . $path;    
        }
            
    }
    
    static public function getForumLinkHtml($engPath, $rusPath = null, $text = '', $userId = null){
        if($text == '')$text = "<span style='font-size:20px;'>Forum Discussion Board</span>";
        
        $result = "<a href='" . self::getForumURL($engPath) . "' target='_blank'>{$text}</a>";
        if(strlen($rusPath) && in_array(AZend_Geo::getCountryCode(), array('RU', 'UA', 'BY', 'EE', 'KZ', 'AZ', 'TJ', 'TM', 'MD', ''))){
            $result.= " <a href='" . self::getForumURL($engPath) . "' target='_blank'><img src='/img/flags/US.gif' alt='Eng' border='0' align='absmiddle'></a> 
            <a href='" . self::getForumURL($rusPath) . "' target='_blank'><img src='/img/flags/RU.gif' alt='Rus' border='0' align='absmiddle'></a>";     
        }    
        return $result;
    }
}    