<?php

class T3WongaSsid {

    static public function get($affid){
        
        $result = (int)T3Db::api()->fetchOne("select ssid from ssid_wonga where affid=? and view=1 limit 1", array($affid));
        if ($result){
            return $result;
        }else{
            $maxssid = (int)T3Db::api()->fetchOne("select max(ssid) from ssid_wonga");
            if ($maxssid){
                $newssid = $maxssid+1;
            }else{
                $newssid = 1;    
            }    
            T3Db::api()->insert("ssid_wonga", array(
                    'ssid'   => $newssid,
                    'affid'  => $affid,   
                    'view'   => 1,
                )); 
            return $newssid;
        }
    }
    
}