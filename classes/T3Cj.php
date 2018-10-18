<?php

class T3Cj {
    static public function getStepPrice($webmaster){
        return round(T3System::getValue("CJ.Integration.StepPrice.{$webmaster}", 50), 2);
    } 
    
    static public function setStepPrice($value, $webmaster){
        T3Db::api()->update("cj_webmasters", array(
            'stepAmount' => $value,
        ), "webmaster='" . (int)$webmaster . "'");
        return T3System::setValue("CJ.Integration.StepPrice.{$webmaster}", round($value, 2));
    }
    
    static public function getPingPrice($webmaster){
        return round(T3System::getValue("CJ.Integration.PingPrice.{$webmaster}", 25), 2);
    } 
    
    static public function setPingPrice($value, $webmaster){
        T3Db::api()->update("cj_webmasters", array(
            'pingPrice' => $value,
        ), "webmaster='" . (int)$webmaster . "'");
        return T3System::setValue("CJ.Integration.PingPrice.{$webmaster}", round($value, 2));
    }    
    
    static public function getWebmasters(){
        $result = array();
        T3Cache_Publisher::load(self::getCIDsArray());
        
        if(count(self::getCIDsArray())){
            foreach(self::getCIDsArray() as $wm){
                $result[$wm] = T3Cache_Publisher::get($wm, false);    
            }
        }   
        
        return $result;
    } 
    
    /**
    * Функция для проведения оплаты через систему Fixed Price.
    * Возвращает цену.
    * 
    * @param T3Lead $lead
    * @param mixed $totalPrice
    */
    static public function getCJPrice(T3Lead $lead, $totalPrice){
        $amount = T3Db::api()->fetchOne("select `step` from cj_subaccount_amount where `subaccount`=? and affid=?", array($lead->subacc_str, $lead->affid));
        
        if($amount === false){
            T3Db::api()->insert("cj_subaccount_amount", array(
                'affid'         => $lead->affid,  
                'subaccount'    => $lead->subacc_str,  
            ));    
        }
        
        $amount = round((float)$amount, 2);
        
        if(($amount + $totalPrice) >= self::getStepPrice($lead->affid)){
            $deltaStepValue = $totalPrice - self::getStepPrice($lead->affid);
            $priceWM = self::getPingPrice($lead->affid);
            $pings = 1;
        }
        else {
            $deltaStepValue = $totalPrice;  
            $priceWM = '0';
            $pings = '0';           
        }
        
        T3Db::api()->update("cj_subaccount_amount", array(
            'step'  => new Zend_Db_Expr("round(`step`+{$deltaStepValue}, 2)"),
            'total' => new Zend_Db_Expr("round(`total`+{$totalPrice}, 2)"),
        ), "`subaccount`=" . T3Db::api()->quote($lead->subacc_str) . " and affid=" . T3Db::api()->quote($lead->affid));
        
        self::addSold($lead, $pings, $priceWM, round(($totalPrice - $priceWM), 2), $totalPrice);
        
        return $priceWM;
    } 
    
    static protected function checkCache(T3Lead $lead){
        if(T3Db::api()->fetchOne("select date from cj_summary where `date`=? and affid=? and `account`=? limit 1", array(substr($lead->datetime, 0, 10), $lead->affid, $lead->subacc_str)) === false){
            T3Db::api()->insert("cj_summary", array(
                'date'      => substr($lead->datetime, 0, 10),
                'affid'     => $lead->affid,
                'account'   => $lead->subacc_str,
            ));    
        }    
    }
    
    /*
    static public $webmasters = array(
        '29563', // CJ 
        '29577', // epic  
        '29657', // neverblue.fixed
        '29699', // neverblue.uk.fixed
        '29750', // neverblue.offerweb
        '29853', // cj_uk_getpayday
        '29854', // cj_uk_speedypaydayloan
        '30105', // cj_uk_eloan_25
        '30106', // cj_uk_spl_40
    );
    */
    
    static protected $webmastersCache = null;
    
    static public function getCIDsArray(){
        if(is_null(self::$webmastersCache)){
            self::$webmastersCache = T3Db::api()->fetchCol("select webmaster from cj_webmasters");    
        }
        return self::$webmastersCache;  
    }
    
    static public function isWebmaster($id){
        if(count(self::getCIDsArray()) && in_array($id, self::getCIDsArray())) return true;
        return false;    
    }
    
    static public function addLead(T3Lead $lead){ 
        if(!self::isWebmaster($lead->affid)) return null;
        self::checkCache($lead);
        
        T3Db::api()->update("cj_summary", array(
            'all' => new Zend_Db_Expr("`all`+1"),         
        ), "`date`=" . T3Db::api()->quote(substr($lead->datetime, 0, 10)) . " and affid=" . T3Db::api()->quote($lead->affid) . " and `account`=" . T3Db::api()->quote($lead->subacc_str));      
    }
    
    static public function addSold(T3Lead $lead, $pings, $wm, $t3, $ttl){ 
        if(!self::isWebmaster($lead->affid)) return null; 
        
        self::checkCache($lead);
        
        T3Db::api()->update("cj_summary", array(
            'solds' => new Zend_Db_Expr("`solds`+1"), 
            'pings' => new Zend_Db_Expr("`pings`+{$pings}"), 
            'wm'    => new Zend_Db_Expr("`wm`+{$wm}"), 
            't3'    => new Zend_Db_Expr("`t3`+{$t3}"),
            'total' => new Zend_Db_Expr("`total`+{$ttl}"),         
        ), "`date`=" . T3Db::api()->quote(substr($lead->datetime, 0, 10)) . " and affid=" . T3Db::api()->quote($lead->affid) . " and `account`=" . T3Db::api()->quote($lead->subacc_str));    
    }
      
}