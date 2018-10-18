<?php

class T3Synh_User {
    protected static $error = null;
    
    static public function getError(){
        return self::$error;    
    }
    
    static protected function setError($message){
        self::$error = $message;    
    }
    
    static public function updateUsers($count = 100){
        $count = (int)$count;
        if($count < 0) $count = 0;
        $users = T3Db::v1()->fetchCol("select id from user where stat='u' and t3v2ID='0' limit {$count}");
        foreach($users as $userID){
            varExport( self::updateOneUser($userID) );    
        }    
    }
    
    static public function getID_fromV1Login($login){
        return T3Db::v1()->fetchOne("select t3v2ID from user where login=?", $login);    
    }
    
    static public function updateOneUser($idUserOldSystem){
        $result = null;
        $userInfo = T3Db::v1()->fetchRow('select * from user where id=?', (int)$idUserOldSystem);
        
        if($userInfo && $userInfo['t3v2ID'] == '0'){
            $a = array();
            
            $a['id']            = $userInfo['id']; 
                    
            $a['login']         = $userInfo['login'];
            $a['pass']          = $userInfo['pass'];
            $a['email']         = $userInfo['email'];
            $a['balance']       = $userInfo['money'];
            $a['lock']          = $userInfo['lock']; 
                    
            $nameArr = explode(" ", trim($userInfo['name']));
            if(count($nameArr) == 2){
                $a['first_name']    = $nameArr[0]; 
                $a['last_name']     = $nameArr[1];    
            }
            else {
                $a['first_name']    = ''; 
                $a['last_name']     = ''; 
            }
            
            $a['icq']           = $userInfo['ICQ']; 
            $a['aim']           = $userInfo['aim']; 
            $a['skype']         = $userInfo['skype']; 
            
            
            $a['country']       = $userInfo['Country'];
            $a['state']         = $userInfo['state'];
            $a['city']          = $userInfo['City']; 
            $a['address']       = $userInfo['address']; 
            
            switch($userInfo['loginAgent']){
                case "DavidTonoyan":    $a['agent'] = "1000036"; break; 
                case "CarynJ":          $a['agent'] = "1000035"; break;
                case "Vlad":            $a['agent'] = "1018365"; break; 
                default:                $a['agent'] = "0";    
            }
            
            $a['refaffid'] = "0";
            if($userInfo['refered']){
                $a['refaffid'] = (int)T3Db::v1()->fetchOne("select t3v2ID from user where login=?", $userInfo['refered']);        
            }
            
            if($userInfo['country_phone_code'] && $userInfo['verified']){
                $a['phone_verify']  = '1';
                $a['phone_code']    = Zend_Filter_Digits::filter($a['Phone']);
                $a['phone_number']  = Zend_Filter_Digits::filter($a['country_phone_code']); ;
            }
            else {
                $a['phone_verify']  = '0';
                $a['phone_code']    = '';
                $a['phone_number']  = '';
            }
              
            
            
            $result = self::saveUser($a);
            
            if(is_numeric($result) && $result){
                T3Db::v1()->update('user',array(
                    't3v2ID' => $result,
                    't3v2Request' => var_export($a, true),
                    't3v2Responce' => $result,
                ),"id={$userInfo['id']}");    
            }
            else {
                T3Db::v1()->update('user',array(
                    't3v2ID' => "-1",
                    't3v2Request' => var_export($a, true),
                    't3v2Responce' => $result,
                ),"id={$userInfo['id']}");      
            }
        }
        
        return $result; 
    } 
    
    static protected function saveUser(array $data){
        $form = new AZend_Form();
        
        $form->addElementAndDecor('text', 'id');
        $form->addElementAndDecor('text', 'login', null, 'T3_NewLogin');
        $form->addElementAndDecor('text', 'pass');
        $form->addElementAndDecor('text', 'email');
        $form->addElementAndDecor('text', 'balance');
        $form->addElementAndDecor('text', 'lock');
        $form->addElementAndDecor('text', 'agent');
        $form->addElementAndDecor('text', 'refaffid', null, false);
        
        $form->addElementAndDecor('text', 'first_name', null, false);
        $form->addElementAndDecor('text', 'last_name', null, false);
        $form->addElementAndDecor('text', 'icq', null, false);
        $form->addElementAndDecor('text', 'aim', null, false);
        $form->addElementAndDecor('text', 'skype', null, false);
        $form->addElementAndDecor('text', 'country', null, false);
        $form->addElementAndDecor('text', 'state', null, false);
        $form->addElementAndDecor('text', 'city', null, false);
        $form->addElementAndDecor('text', 'address', null, false);
        
        $form->addElementAndDecor('text', 'phone_verify');
        $form->addElementAndDecor('text', 'phone_code', null, false);
        $form->addElementAndDecor('text', 'phone_number', null, false);
        
        if($form->isValid($data)){
        
            //$agent = false; // не присваивать агента тому у кго его нет. Поменять на 0, для того что бы присваивать случайного агента.
            //if($form->getValue('agent')) $agent = $form->getValue('agent');
            $agent = $form->getValue('agent'); 
            if($agent == '') $agent = '0';
            
            /** @var T3WebmasterCompany */
            $company = T3WebmasterCompany::createNewCompany($form->getValue('login'), $form->getValue('login'), 0, $agent);
            
            $company->updateBalance($form->getValue('balance'));
            
            $company->refaffid                  =   (int)$form->getValue('refaffid');
            $company->T3LeadsVersion1_ID        =   $form->getValue('id'); 
            $company->T3LeadsVersion1_Login     =   $form->getValue('login'); 
            
            switch($form->getValue('lock')){
                case 0: $company->status = 'activ'; break;
                case 1: $company->status = 'hold';  break;
                case 2: $company->status = 'lock';  break;    
            }
            
            $company->saveToDatabase();
            
            // добавление записи о изменение баланса
            if($company->balance != 0){
                T3System::getConnect()->insert('webmasters_old_leads', array(
                    'webmaster_id'      =>  $company->id,
                    'action_sum'        =>  $company->balance,
                    'action_datetime'   =>  new Zend_Db_Expr('NOW()'),
                ));
                
                T3Report_Summary::addBalance(
                    $company->id,
                    $company->balance,
                    date('Y-m-d H:i:s')
                );
            }
            
            /** @var T3User */
            $user = T3Users::createNewPartnerAccount(
                $company, 
                $form->getValue('login'), 
                $form->getValue('pass'), 
                $form->getValue('first_name') . " " . $form->getValue('last_name'), 
                $form->getValue('email'),
                array(
                    'first_name'        => $form->getValue('first_name'),
                    'last_name'         => $form->getValue('last_name'),
                    'country'           => $form->getValue('country'),
                    'state'             => $form->getValue('state'),
                    'city'              => $form->getValue('city'),  
                    'address'           => $form->getValue('address'),
                    'phones'            => array(array(
                        'type'  => 'main', 
                        'phone' => $form->getValue('phone_code') . "." . $form->getValue('phone_number'),
                    )),
                    'icq'               => $form->getValue('icq'),
                    'aim'               => $form->getValue('aim'),
                    'skype'             => $form->getValue('skype'),
                )
            );
            
            $user->from_version1    = 1;
            $user->email_vf         = 1;
            $user->activ            = 1;
            $user->ban              = $form->getValue('lock');
            
            $user->saveToDatabase();
            
            return $company->id;
            
        }
        else {
            self::setError("Form Error.\r\n\r\n" . var_export($form->getErrors(), true));
            return false;
        }
    }   
}