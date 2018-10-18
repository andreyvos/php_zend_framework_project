<?php

class T3Cache_LeadsBody {
    static public $data;
    
    static public function load($ids){
        if(count($ids)){
            $ids = array_unique($ids);
            $data = T3Db::apiReplicant()->fetchPairs("select id,ip_address from leads_data where id in ('" . implode("','", $ids) . "')"); 

            foreach($ids as &$id){
                if(isset($data[$id]))   self::$data[$id] = $data[$id];    
                else                    self::$data[$id] = $id;        
            }
            
            T3Cache_LeadsID::load($ids);
        } 
    } 
    
    
    static public function get($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        T3Cache_LeadsTabSetup::setup();
        
        return "<a 
            href='/en/account/lead/edit-body?id=" . T3Cache_LeadsID::get($id, false) . "'
            onclick=\"if(document.leadTab.showLeadBody('" . T3Cache_LeadsID::get($id, false) . "')){return false}\"
        >Lead Info</a>";
    } 
}
