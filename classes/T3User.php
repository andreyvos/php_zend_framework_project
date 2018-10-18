<?php

TableDescription::addTable('users', array(
    // main info
    'id',
    'role',
    'company_id',
    'login',
    'hash',
    'password',
    'verification_code',
    'nickname',
    'email',

    'from_version1',
    'from_version1_status',
    'phone_vf',
    'email_vf',
    'activ',
    'ban',
    'ip_filter',
    'email_ip_recover',
    'registration_datetime',
    'activation_type',
    'activation_date',
    'email_news',
    'signature',


    // personal info
    'first_name',
    'last_name',
    'country',
    'state',
    'city',
    'postal_code',
    'address',
    'phones',
    'icq',
    'aim',
    'skype',

    'bestContactMethod',
    'subscribe',
    'reciveTickets',

    'flaphone_login',
    'flaphone_password',
    'ipad',
    'only_office_ip',
    'email_billing'
));       
 

class T3User extends DbSerializable {

    public $id; 
    public $role; 
    public $company_id;
    public $login;
    public $password;
    public $verification_code;
    public $nickname;
    public $email;
    public $email_billing;
    public $from_version1 = 0;
    public $from_version1_status = 'none';
    
    public $flaphone_password;
    public $flaphone_login;
    
    public $phone_vf = 0;
    public $email_vf = 0;
    public $activ;
    public $ban;
    public $temp;
    public $ip_filter;
    public $email_ip_recover;
    public $registration_datetime;
    public $activation_type;
    public $activation_date;
    public $email_news;
    public $signature;
    public $hash;
    
    
    public $first_name;
    public $last_name;
    public $country;
    public $state;
    public $city;
    public $postal_code;
    public $address;
    public $phones;
    public $icq;
    public $aim;
    public $skype;
    
    public $bestContactMethod; 
    public $subscribe;
    public $reciveTickets;

    public $companyObject;

    protected $groups;

    public $isGuest = false;

    public $webmaster;
    public $buyer;
    public $ipad = 1;
    public $only_office_ip = 0;

    public function  __construct() {

        if (!isset($this->className))$this->className = __CLASS__;

        parent::__construct();
        $this->tables = array('users');

        $this->ban = 0;
        $this->activation_type = 'automatic';
        $this->email_news = 1;
        $this->phone_verification = 0;
        $this->active = 0;
        $this->ban = 0;
        $this->ip_filter = 0;
        $this->subscribe = 1;
        $this->reciveTickets=1;
    }

    public function getWebmaster($lazy = true) {

      if ($lazy && !is_null($this->webmaster))
        return $this->webmaster;

      $this->webmaster = new T3WebmasterCompany();

      if($this->webmaster->fromDatabase($this->company_id) === false)
        return false;

      return $this->webmaster;
        
    }

    public function validForRegistration() {



    }

    public function makeActive() {
        $this->activ = 1;
        $this->activation_date = mySqlDateTimeFormat();
    }

    public function register() {
        $this->registration_datetime = mySqlDateTimeFormat();
        $this->insertIntoDatabase();
        $this->saveGroupsToDatabase();
    }

    public function getWebmasterSyncKey(){
        return sha1("destinationleadssalt111" . $this->hash . "endsalt");
    }

    
    /*
    public function getGroups($lazy = true) {
        if ($lazy && !is_null($this->groups))
            return $this->groups;
        if (!$this->isGuest)
            $data = $this->database->fetchAll("
                                              SELECT ug.group_system_name
                                              FROM users as u
                                              RIGHT JOIN users_groups as ug
                                              ON u.id = ug.user_id
                                              WHERE u.id = ?
                                              ", array($this->id));
        else
            $guest = array('group_system_name' => 'guest');
        $groups = $this->system->users->getGroups();
        $this->groups = array();
        foreach($data as $v)
        $this->groups[$v['group_system_name']] = $groups[$v['group_system_name']];
        return $this->groups;
    }

    public function addToGroup($groupSystemName) {
        $groups = $this->system->users->getGroups();
        $this->getGroups();
        $this->groups[$groupSystemName] = $groups[$groupSystemName];
    }

    public function addToGroups(array $systemNames) {
        foreach($systemNames as $v)
        $this->addToGroup($v);
    }

    public function saveGroupsToDatabase() {

        $this->database->delete('users_groups', "user_id = " . $this->database->quote($this->id));
        if (count($this->getGroups())==0)
            return;

        $rows = array();
        foreach($this->groups as $v)
        $rows[] = array($this->id, $v->system_name);

        insertMultiple($this->database, 'users_groups', array('user_id', 'group_system_name'), $rows);

    }

    public function getGroups_Array() {
        if (!$this->isGuest)
            return $this->database->fetchAll('
                                             SELECT *
                                             FROM users_groups
                                             WHERE user_id = ?
                                             ', array($this->id));
        else
            return array('group_system_name' => 'guest');
    }

    public function getGroupsDetailed_Array() {
        if (!$this->isGuest) {
            return $this->database->fetchAll('
                                             SELECT ugn.*
                                             FROM users_groups AS ug
                                             LEFT JOIN users_groupnames AS ugn
                                             ON ugn.system_name = ug.group_system_name
                                             WHERE user_id = ?
                                             ', array($this->id));
        } else {
            return $this->database->fetchAll('
                                             SELECT *
                                             FROM users_groupnames
                                             WHERE system_name = ?
                                             ', array('guest'));
        }
    }
    */
    
    public function createReSendMailKey(){
        return md5("reSendEmail - " . $this->id . " - " . $this->hash);    
    }

    public static function createFromDatabase($conditions) {
        return self::createFromDatabaseByClass($conditions, __CLASS__);
    }

    public static function createFromArray(&$array) {
        return self::createFromArrayByClass($array, __CLASS__);
    }
    
    public function toArray($tables = null){
        $tempPhones = $this->phones;
        $this->phones = serialize($tempPhones);
        
        $return = parent::toArray($tables);
        
        $this->phones = $tempPhones;
        
        return $return;
    }

    public function fromArray($array){
        parent::fromArray($array);
        
        $this->phones = unserialize((string)$this->phones);
    }
    
    public function getUserCompany(){
	    if(is_null($this->companyObject)){
            if($this->role === "webmaster"){
                $this->companyObject = new T3WebmasterCompany();
	            $this->companyObject->fromDatabase($this->company_id);
	        }
	        else if($this->role === "buyer"){
	            $this->companyObject = new T3BuyerCompany();
	            $this->companyObject->fromDatabase($this->company_id);
	        }
            else {
                $this->companyObject = new T3AdminCompany();     
            }
        }
        
        return $this->companyObject;
    }
    
    /**
    * Получить ссылку на страницу, которую пользователь должен видеть после входа в аккаунт
    * 
    * @return string 
    */
    public function getLinkFirstPage(){
        switch ($this->role) {
            case "admin":               return ROOT . "/account/";
            case "webmaster":           return ROOT . "/account/";
            case "buyer":               return ROOT . "/account/";
            case "webmaster_agent":     return ROOT . "/account/";
            case "buyer_agent":         return ROOT . "/account/";
            case "call_agent":          return ROOT . "/account/"; 
            case "accounting":          return ROOT . "/account/"; 
            default:                    return ROOT . "/account/";    
        }
    }
    
    /**************************************************************************************
    * Методы проверки принадлежности роли к группам
    */
    
    public function isRoleAdmin(){
        if($this->role == 'admin') return true;
        return false;    
    }
    
    public function isRoleSEO(){
        if($this->role == 'seo') return true;
        return false;    
    }
    
    public function isRoleWebmaster(){
        if($this->role == 'webmaster') return true;
        return false;     
    }
    
    public function isRoleBuyer(){
        if($this->role == 'buyer') return true;
        return false;     
    }
    
    public function isRoleWebmasterAgent(){
        if($this->role == 'webmaster_agent') return true;
        return false;     
    }
    
    public function isRoleBuyerAgent(){
        if($this->role == 'buyer_agent') return true;
        return false;     
    }
    
    public function isRoleCallAgent(){
        if($this->role == 'call_agent') return true;
        return false;     
    }
    
    public function isRoleAccounting(){
        if($this->role == 'accounting') return true;
        return false;     
    }
    
    public function isRoleCopywriter(){
        if($this->role == 'copywriter') return true;
        return false;     
    }
    
    /**
    * Записать переменную в реестр пользователя
    * 
    * @param string $name
    * @param mixed $value
    */
    public function setRegistrValue($name, $value){ 
        if(!T3Db::api()->fetchOne('select count(*) from system_users_registr where `iduser`=? and `name`=?', array($this->id, $name))){
            try{
                T3Db::api()->insert('system_users_registr', array(
                    'iduser' => $this->id,
                    'name' => $name,
                    'value' => serialize($value),
                ));
                
                return $this;   
            } 
            catch (Exception $e){}
        }
        
        T3Db::api()->update('system_users_registr', array(
            'value' => serialize($value),
        ), "`iduser`=" . T3Db::api()->quote($this->id) . " and `name`=" . T3Db::api()->quote($name)); 
        
        return $this;
    }
    
    /**
    * Получить переменную из реестра пользователя
    * 
    * @param string $name
    * @param mixed $default
    * 
    * @return mixed
    */
    public function getRegistrValue($name, $default = null){
        $value = T3Db::api()->fetchOne('select `value` from system_users_registr where `iduser`=? and `name`=?', array($this->id, $name));
        
        if($value !== false)    return unserialize($value);
        else                    return $default;    
    }
    
    public function copywriterRoleMode(){
        return $this->getRegistrValue('copywriterMode', 'copywriter');
            
    }
    
    public function getForumLink($path = null){
        $key = base64_encode(sprintf('%s--|--%s--|--%s',$_SERVER['REMOTE_ADDR'],$this->login,md5($this->password)));
        return sprintf("http://forum.t3leads.com/login.php?do=fromt3login&key=%s&path=%s",$key,$path);
    }
    
    //Добавил для акции...
    public function close_ipad()
    {
        $this->ipad = 1;
        $this->saveToDatabase();
    }

}

