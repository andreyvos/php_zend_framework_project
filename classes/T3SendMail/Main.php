<?php

class T3SendMail_Main {
    static public function createLink($type, $options){
        if(isset($_SERVER['HTTPS']))    $p = "https://";
        else                            $p = "http://";  

        return "{$p}{$_SERVER['HTTP_HOST']}/account/mails/send-email?disableLayout=1&ttype=" . urldecode($type) . "&topt=" . urlencode(Zend_Json::encode($options));    
    }
    
    static public function createLinkInShowPage($type, $options){
        if(isset($_SERVER['HTTPS']))    $p = "https://";
        else                            $p = "http://";  

        return "{$p}{$_SERVER['HTTP_HOST']}/account/mails/show-mail?disableLayout=1&ttype=" . urldecode($type) . "&topt=" . urlencode(Zend_Json::encode($options));    
    }
    
    static public function run($type, $options, $default){
        // to cc bcc fromEmail fromName subject text 
        
        if(Zend_Loader::isReadable(dirname(__FILE__) . DS . "Template" . DS . "{$type}.php")){
            $className = "T3SendMail_Template_{$type}";
            
            $template = new $className();
            $default = $template->run(Zend_Json::decode($options), $default);        
        }
        
        
        return $default;
    }    
}