<?php

class T3Cache_WebmasterChannels {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        $dataTemp = T3Db::apiReplicant()->fetchAll("select id,title,channel_type from channels where id in ('" . implode("','", $ids) . "')"); 

        $data = array();
        if(count($dataTemp)){  
            foreach($dataTemp as $el){
                $data[$el['id']] = $el;   
            }
        }
        
        $postChannels = array();
        $formChannels = array();
        
        foreach($ids as &$id){
            if(isset($data[$id])){
                self::$data[$id] = $data[$id];
                if($data[$id]['channel_type'] == 'post_channel'){
                    $postChannels[] = $id;    
                }
                else {
                    $formChannels[] = $id;
                }
            }     
            else {
                self::$data[$id] = array(
                    'id'            => $id,
                    'title'         => null,
                    'channel_type'  => null,
                );
            }       
        }
        
        if(count($postChannels)){
            $getIDs = T3Db::apiReplicant()->fetchPairs("select id, getID from channels_post where id in ('" . implode("','", $postChannels) . "')");
            
            foreach($getIDs as $idOld => $getID){
                self::$data[$idOld]['id'] = $getID;        
            }
        }

        if(count($formChannels)){
            $isMobile = T3Db::apiReplicant()->fetchPairs("select id, is_mobile from channels_js_forms where id in ('" . implode("','", $formChannels) . "')");

            foreach($isMobile as $id => $isMobile){
                self::$data[$id]['is_mobile'] = $isMobile;
            }
        }
    } 
    
    static public function render($id, $absolutePath = false){
        if($id == 0) return "All";
        
        if(!isset(self::$data[$id])) self::load(array($id));
        
        $domain = "";
        if($absolutePath){
            $domain = "https://{$_SERVER['HTTP_HOST']}";    
        }
        
        if(self::$data[$id]['channel_type'] == 'js_form'){
            if(isset(self::$data[$id]['is_mobile']) && self::$data[$id]['is_mobile']){
                return  "<a style='color:#E55;text-decoration:none;cursor:pointer' href='{$domain}/en/account/webmaster-channels/channels-route?id=" .
                        self::$data[$id]['id'] . "'>Mobile</a>: ". self::renderFormTitle(self::$data[$id]['title']);
            }
            else {
                return  "<a style='color:#44E;text-decoration:none;cursor:pointer' href='{$domain}/en/account/webmaster-channels/channels-route?id=" .
                        self::$data[$id]['id'] . "'>Form</a>: ". self::renderFormTitle(self::$data[$id]['title']);
            }
        }
        else if(self::$data[$id]['channel_type'] == 'post_channel') {
            return  "<a style='color:#5A5;text-decoration:none;cursor:pointer' href='{$domain}/en/account/webmaster-channels/channels-route?id=" . self::$data[$id]['id'] . "'>Post</a>: ". self::$data[$id]['title'];      
        }
        else {
            return "-";
        }
    }
    
    static protected function renderFormTitle($title){
        $text = $title;
        
        MyZend_Site::addCSS('report/webmasterChannel.css');
        
        if(strlen($text) > 0){
            $endDomain = strpos($text, "/");
            $strLen = strlen($text);
            
            if($endDomain === false || $endDomain == $strLen-1){
                if($endDomain == $strLen-1){
                    $text = substr($text,0,strlen($text)-1);  
                }  
                
                return "<a style='cursor:pointer;color:#069;' target='_blank' href='http://{$text}'>{$text}</a>";    
            }
            else {
                return "<a style='cursor:pointer;color:#069;' target='_blank' href='http://{$text}'>" . substr($text, 0, $endDomain) . "</a>" . 
                "<div class='webmasterChannel'><span class='wc2'><a target='_blank' href='http://{$text}'>" . substr($text , $endDomain) . "</a></span><span class='wc1'>/..</span></div>";   
            } 
        }  
    } 
}