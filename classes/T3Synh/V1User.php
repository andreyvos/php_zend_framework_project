<?php

class T3Synh_V1User {
    protected static $error = null;
    
    static public function getError(){
        return self::$error;    
    }
    
    static protected function setError($message){
        self::$error = $message;    
    }
    
    /**
    * Получение логина пользователя в старой системе по ID вебмастера в новой
    * 
    * @param mixed $V2_userId
    * @return string V1 Login or false
    */
    static public function getV1Login($V2_userId){
        $V2_userId = (int)$V2_userId; 
        return T3Db::v1()->fetchOne("select login from user where t3v2ID=?", (int)$V2_userId);    
    }
    
    /**
    * Получение массива информации о пользователе из старой системы
    * 
    * @param mixed $V2_userId
    * @return string V1 Login or false
    */
    static public function getUserArray($V2_userId){
        $V2_userId = (int)$V2_userId; 
        return T3Db::v1()->fetchRow("select * from user where t3v2ID=?", (int)$V2_userId);    
    }
    
    /**
    * Получение ID пользователя в старой системе по ID вебмастера в новой
    * 
    * @param mixed $V2_userId
    * @return string V1 ID or false
    */
    static public function getV1ID($V2_userId){
        $V2_userId = (int)$V2_userId; 
        return T3Db::v1()->fetchOne("select id from user where t3v2ID=?", (int)$V2_userId);    
    }
    
    /**
    * Изменение баланса пользователя в старой системе, которое не попадает в новую.
    * 
    * @param mixed $userId - ID в новой
    * @param mixed $sum
    */
    static public function updateBalance_NotSync($userId, $sum){
        $sum = round($sum, 2);
        
        $login = self::getV1Login($userId);
         
        if($sum != 0 && $login !== false){
            T3Db::v1()->query("set @notSyncBalance = 1");
            T3Db::v1()->query("update `user` set `money` = `money`+{$sum} where `login`='{$login}'");
            T3Db::v1()->query("set @notSyncBalance = null");
            return true;  
        } 
        
        return false;         
    }
    
    
    
    /**
    * Изменение баланса пользователя в старой системе, которое не попадает в новую.
    * 
    * @param mixed $userId - ID в новой
    * @param mixed $sum
    */
    static public function updateBalance2_NotSync($oldLogin, $sum){
        $sum = round($sum, 2);
         
        if($sum != 0){
            T3Db::v1()->query("set @notSyncBalance = 1");
            T3Db::v1()->query("update `user` set `money` = `money`+{$sum} where `login`='{$oldLogin}'");
            T3Db::v1()->query("set @notSyncBalance = null");
            return true;  
        } 
        
        return false;         
    }
    
    /**
    * Изменение баланса пользователя в старой системе
    * 
    * @param mixed $userId - ID в новой
    * @param mixed $sum
    */
    static public function updateBalance($userId, $sum){
        $sum = round($sum, 2);
        
        $login = self::getV1Login($userId);
         
        if($sum != 0 && $login !== false){
            T3Db::v1()->query("update `user` set `money` = `money`+{$sum} where `login`='{$login}'");
            return true;  
        } 
        
        return false;         
    }
    
    /**
    * Изменение баланса пользователя в старой системе
    * 
    * @param mixed $userId - ID в новой
    * @param mixed $sum
    */
    static public function updateBalance2($oldLogin, $sum){
        $sum = round($sum, 2);
         
        if($sum != 0){
            T3Db::v1()->query("update `user` set `money` = `money`+{$sum} where `login`='{$oldLogin}'");
            return true;  
        } 
        
        return false;         
    }
    
    /**
    * Создание копии пеймента в старой системе
    * При этом:
    * 1. В V1 с баланса пользователя снимается сумма, которая не попадает на синхронизацию
    * 2. Добавляеся запись в пейменты
    * 
    * @param int $V2paymentId - ID пеймента в новой системе 
    * @param int $userId - ID пользователя в новой системе
    * @param float $sum - Общая сумма выплаты за V1Balances
    * @param text $payInfo - Информация о том куда была сделанна выплата
    * @param float $fee - сумма коммисии за V1Balances
    * @param int $hold - количесво дней холда
    * @param string date YYYY-MM-DD $date - если NULL, то будет текущая дата
    */
    static public function createPayment($V2paymentId, $userId, $sum, $payInfo, $fee, $hold, $date = null){
        $result = true; 
        
        if(is_null($date))$date = date("Y-m-d");
        
        T3Db::v1()->beginTransaction();
        try {
            if(self::updateBalance_NotSync($userId, -($sum+$fee))){
                T3Db::v1()->insert('payment_history', array(
                    'login'             => self::getV1Login($userId),
                    'pay_datetime'      => $date,
                    'sum'               => $sum,
                    'pay_info'          => $payInfo,
                    'commision'         => $fee,
                    'hold'              => $hold+1,
                    'hold_appr'         => $hold+1,
                    'pay_appr'          => '0',
                    'hold_auto'         => $hold+1,
                    'pay_bonus'         => '0', 
                    'hold_bonus'        => $hold+1, 
                    'hold_bonuscredit'  => $hold+1,
                    'sync'              => $V2paymentId, 
                ));
            }
            
            T3Db::v1()->commit();
        } 
        catch (Exception $e) {
            T3Db::v1()->rollBack();
            $result = false; 
            self::setError($e->getMessage());
        }
        
        return $result;           
    }
    
    
    static public function updateAllPaymentsSettings($login = null){
        
        if(is_null($login)){
            $all = T3Db::v1()->fetchAll("select login,t3v2ID,paymethod,WebMoney,PayPal,ePassporte,Bank_Wire_Transfer,Company_check from user where t3v2ID>0 and paymethod");
        }
        else {
            $all = T3Db::v1()->fetchAll("select login,t3v2ID,paymethod,WebMoney,PayPal,ePassporte,Bank_Wire_Transfer,Company_check from user where t3v2ID>0 and paymethod and login=?", $login);       
        }
        
        //varExport($all);
        
        if(is_array($all) && count($all)){
            foreach($all as $user){
                //varExport($user);
                
                T3Db::api()->delete('webmasters_payments_systems', "webmaster_id={$user['t3v2ID']}");
                
                $check_part = '0';
                $epass_part = '0';
                $paypal_part = '0';
                $webmoney_part = '0';
                $wire_part = '0';
                
                $configured = "1";
                $systems_used = "";
                
                if($user['paymethod'] == "WebMoney"){
                    $webmoney_part = "1"; 
                    $systems_used = "webmoney";   
                }
                else if($user['paymethod'] == "PayPal"){
                    $paypal_part = "1";
                    $systems_used = "paypal";     
                }
                else if($user['paymethod'] == "Fethard"){
                    $configured = "0";    
                }
                else if($user['paymethod'] == "Bank_Wire_Transfer"){
                    $wire_part = "1";
                    $systems_used = "wire";     
                }
                else if($user['paymethod'] == "ePassporte"){
                    $epass_part = "1"; 
                    $systems_used = "epass";    
                }
                else if($user['paymethod'] == "Company_check"){
                    $check_part = "1"; 
                    $systems_used = "check";   
                }
                
                T3Db::api()->insert('webmasters_payments_systems', array(
                    'webmaster_id'  => $user['t3v2ID'],
                    'systems_used'  => $systems_used,
                    'configured'    => $configured,
                    'check_part'    => $check_part,
                    'check_data'    => serialize(array('data' => $user['Company_check'])),
                    'epass_part'    => $epass_part,
                    'epass_data'    => serialize(array('data' => $user['ePassporte'])),
                    'paypal_part'   => $paypal_part,
                    'paypal_data'   => serialize(array('data' => $user['PayPal'])),
                    'webmoney_part' => $webmoney_part,
                    'webmoney_data' => serialize(array('data' => $user['WebMoney'])),
                    'wire_part'     => $wire_part,
                    'wire_data'     => serialize(array(
                        'name_on_account'   => '',
                        'bank_name'         => '',
                        'account_number'    => '',
                        'swift'             => '',
                        'bank_address'      => '',
                        'bank_phone'        => '',
                        'owner_address'     => '',
                        'owner_phone'       => '',
                        'comment'           => $user['Bank_Wire_Transfer'],
                    )),

                    'ach_3_business_days_part'     => 0,
                    'ach_3_business_days_data'     => null,

                    'ach_next_business_day_part'     => 0,
                    'ach_next_business_day_data'     => null,

                    'ach_same_day_part'     => 0,
                    'ach_same_day_data'     => null,

                ));    
            }
        }
    }
    
    static public function getAgentLogin($v2_ID, $ifNot = 'null'){
        $array = array(
            '1000035' => 'CarynJ',
            '1000036' => 'DavidTonoyan',
            '1018365' => 'Vlad',
            '1019879' => 'MorganGethers',
        );
        
        return isset($array[$v2_ID]) ? $array[$v2_ID] : $ifNot;
    }  
}