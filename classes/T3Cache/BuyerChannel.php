<?php

class T3Cache_BuyerChannel {
    static protected $data;
    static protected $dataBuyers;
    
    static protected function loadBuyers($ids){
            
    }
    
    static public function load($ids){
        $ids = array_unique($ids);
        
        if(is_array($ids) && is_array(self::$data) && count($ids) && count(self::$data)){
            foreach($ids as $key => $id){
                if(isset(self::$data[$id])){
                    unset($ids[$key]);   
                }
            }    
        }
        
        if(is_array($ids) && count($ids)){
            $ids = array_values($ids);
            
            $dataTemp = T3Db::apiReplicant()->fetchAll("select id, title, buyer_id from `buyers_channels` where id in ('" . implode("','", $ids) . "')");
            $data = array();
            $idsBuyers = array(); 
            foreach($dataTemp as $el){
                $data[$el['id']] = $el;
                if(!isset($dataBuyers[$el['buyer_id']]))$idsBuyers[] = $el['buyer_id'];       
            }
            
            
            
            if(count($idsBuyers)){
                $idsBuyers = array_values($idsBuyers);
                $dataB = T3Db::apiReplicant()->fetchAll("select id, systemName from `users_company_buyer` where id in ('" . implode("','", $idsBuyers) . "')"); 
                foreach($dataB as $b){
                    self::$dataBuyers[$b['id']] = $b['systemName'];    
                }
            }
            
            foreach($data as $key => $el){
                $data[$key]['Buyer'] = ifset(self::$dataBuyers[$el['buyer_id']], 'Unknown');    
            }
            

            foreach($ids as $id){
                if(isset($data[$id]))   self::$data[$id] = $data[$id];      
                else                    self::$data[$id] = "-";
            } 
        }
    } 
    
    static public function get($id, $absolute = false){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        $domain = ""; 
        if($absolute){
            $host = $_SERVER['HTTP_HOST'];
            if($host == 'system.t3leads.com')$host = "t3leads.com";
            $domain = "https://{$host}";    
        }

        $cUser = T3Users::getCUser();
        if(is_object($cUser) && $cUser instanceof T3User && $cUser->id){
            if($cUser->isRoleWebmasterAgent() && !T3Users_AgentManagers::isPubManager() && !T3Users::getCUser()->id == '1033477' /* Bao Ta */){
                return self::$data[$id]['id'];
            }
        }
        
        if(is_array(self::$data[$id]))  return  "<nobr><a style='color:#09C' href='{$domain}/en/account/buyers/main?id=" . self::$data[$id]['buyer_id'] . "'>" . self::$data[$id]['Buyer'] . "</a> :".
                                                " <a style='color:#036' href='{$domain}/en/account/posting/main?id=" . self::$data[$id]['id'] . "'>" . self::$data[$id]['title'] . "</a></nobr>";
        else                            return self::$data[$id];
    }
    
    static public function getUnlinks($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(is_array(self::$data[$id]))  return self::$data[$id]['Buyer'] . " : " . self::$data[$id]['title'];
        else                            return self::$data[$id];      
    }
    
    
    
    static public function sendLogOtherWay($id, $leadID, $absolute = false){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        $domain = ""; 
        if($absolute){
            $host = $_SERVER['HTTP_HOST'];
            if($host == 'system.t3leads.com')$host = "t3leads.com";
            $domain = "https://{$host}";    
        }
       // http://t3leads.com/en/account/lead/log?id=79801.26918&buyer_channel_id=10306
        if(is_array(self::$data[$id]))  return  " <a style='color:#999' href='{$domain}/en/account/lead/log?id=". $leadID ."&buyer_channel_id=" . self::$data[$id]['id'] . "'>Send LOG</a>";
        else                            return self::$data[$id];
    }
    
    static public function getTitle($id){
        if(!isset(self::$data[$id])) self::load(array($id)); 
        
        if(is_array(self::$data[$id]))  return  self::$data[$id]['Buyer'] . " : " . self::$data[$id]['title'];
        else return self::$data[$id];
    } 
}