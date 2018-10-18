<?php

class T3Cache_Url {
    static public function render($url, $link = null){
        if($link === null) $link = $url;
        
        if(substr($url, 0, 7) == 'http://'){
            $text = substr($url, 7);
            $protocol = 'http://';
        }
        else if(substr($url, 0, 8) == 'https://'){
            $text = substr($url, 8);
            $protocol = 'https://';
        }
        else {
            $text = $url;
            $protocol = '(error) ';
        }
        
        MyZend_Site::addCSS('report/webmasterChannel.css');
        
        if(strlen($text) > 0){
            $endDomain = strpos($text, "/");
            $strLen = strlen($text);
            
            if($endDomain === false || $endDomain == $strLen-1){
                if($endDomain == $strLen-1){
                    $text = substr($text,0,strlen($text)-1);  
                }  
                
                return "<a style='cursor:pointer;color:#069;' target='_blank' href='{$link}'><span style='color:#39B'>{$protocol}</span>{$text}</a>";    
            }
            else {
                return "<a style='cursor:pointer;color:#069;text-decoration:none;' target='_blank' href='{$link}'><span style='color:#ACF'>{$protocol}</span>" . 
                substr($text, 0, $endDomain) . "<span style='color:#5E9EFF'>" . substr($text , $endDomain) . "</span></a></div>";   
            } 
        }
    }
}