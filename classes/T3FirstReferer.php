<?php

/**
* Пользователь, пришедший на сайт (с некоторыми исключениями) должен иметь First Referer ссылку
* эта ссылка хранится в куках в виде ID 
* этот ID кроме редирект ссылких хранит еще и и нформацию о вебмастерах, которые на него зарегистрировались 
*/

class T3FirstReferer {
    static protected $refererID = 0;
    
    static public function getID(){
        self::init();
        return self::$refererID;    
    }
    
    static public function init(){ 
        
        if(!self::$refererID){
            if(isset($_COOKIE['fref']) && ($frefID = T3Ad::decodeSecureID($_COOKIE['fref']))){
                // ID найден и он реальный
                self::$refererID = $frefID;
                
                return self::$refererID;
            }   
            else { 
                // First Referer нет, попытаться его создать
                if(isset($_SERVER['HTTP_REFERER'])){
                    
                    $host = explode(".", parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST));
                    
                    if(count($host) == 1) $host = $host[0];
                    else                  $host = $host[count($host) - 2] . "." . $host[count($host) - 1];
                    
                    if(!in_array($host, array('t3leads.com', 't3.lh', 'f.t3leads.com'))){
                        T3Db::api()->insert("first_referer", array(
                            'datetime'  => date('Y-m-d H:i:s'),
                            'ip'        => AP_Http::get_ip_num(),
                            'url'       => $_SERVER['HTTP_REFERER'],
                            'link'      => AP_Http::get_http_url(),
                        ));
                        
                        self::$refererID = T3Db::api()->lastInsertId();
                        
                        $host = explode(".", ifset($_SERVER['HTTP_HOST'], "t3leads.com"));
                        
                        if(count($host) == 1) $host = $host[0];
                        else                  $host = $host[count($host) - 2] . "." . $host[count($host) - 1];
                        
                        setcookie("fref", T3Ad::createSecureID(self::$refererID), time() + 3600 * 34 * 3000, "/", ".{$host}");
                    }
                } 
            }  
        }
        
        return self::$refererID;
    }
    
    static public function getFirstRefererURL($id = null){
        self::init();
        
        if(!is_null($id)){
            return (string)T3Db::api()->fetchOne("select url from first_referer where id=?", $id);        
        }
        
        if(self::$refererID){
            return (string)T3Db::api()->fetchOne("select url from first_referer where id=?", self::$refererID);
        }   
        
        return "";
    }
    
    static public function addWebmaster($webmaster){
        self::init();
        
        if(self::$refererID){
            T3Db::api()->query(
                "update first_referer set webmasters = concat(ifnull(`webmasters`, ''), '{$webmaster},'), webmasters_count = webmasters_count+1 where id=?", 
                self::$refererID
            );    
        }
    }
}