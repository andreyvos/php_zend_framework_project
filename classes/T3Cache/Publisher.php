<?php

class T3Cache_Publisher {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        $data = T3Db::apiReplicant()->fetchPairs("select id, systemName from `users_company_webmaster` where id in ('" . implode("','", $ids) . "')"); 

        foreach($ids as &$id){
            if(isset($data[$id]))   self::$data[$id] = $data[$id];  
            else if($id == 3000)    self::$data[$id] = "T3Leads";     
            else                    self::$data[$id] = "Unknown: {$id}";        
        } 
    } 
    
    static public function get($id, $link = true, $absolutePath = false){
        if($id == 0)    return "";
        
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        $domain = "";
        if($absolutePath){
            $domain = "https://{$_SERVER['HTTP_HOST']}";    
        }
        
        if(T3Users::getCUser()->isRoleAdmin() || T3Users::getCUser()->isRoleWebmasterAgent() || T3Users::getCUser()->isRoleAccounting()){
            if(!$link)              return self::$data[$id];
            else if($id == 3000)    return "<a style='text-decoration:none;'>" . self::$data[$id] . "</a>";
            else                    return "<a href='{$domain}/en/account/webmasters/main/id/{$id}' target='_blank'>" . self::$data[$id] . "</a>";
        }
        else {
            if(!$link)              return self::$data[$id];
            else                    return "<a style='text-decoration:none;'>" . self::$data[$id] . "</a>";      
        }
    } 
}