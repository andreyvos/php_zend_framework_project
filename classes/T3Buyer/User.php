<?php
/**
* Класс Пользователя для каопаний баеров
* 
* 
* @author Anton S. Panfilov
* @version 1.0
* @copyright anton.panfilov@gmail.com  
*/
  
  
AZend_DB_ObjectSettings::addTable('users', array(
    'id',
    'company_id',
    
    'nickname',
    'email',
    
    'first_name',
    'last_name',
    
    'phones' => array('serialize' => true),
    'icq',
    'aim',
    'skype', 
));

class T3Buyer_User extends AZend_DB_Object {
    protected $dbObj_tableName = 'users';
    

    protected $id;  
    public $company_id;
     
    public $nickname;
    public $emailemail;
    
    public $first_name;
    public $last_name;
    
    public $phones;
    public $icq;
    public $aim;
    public $skype; 
                
}