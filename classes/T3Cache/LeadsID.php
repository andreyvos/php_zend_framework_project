<?php

class T3Cache_LeadsID {
    static public $data;
    
    static public function load($ids){
        if(count($ids)){
            $ids = array_unique($ids);
            $data = T3Db::apiReplicant()->fetchPairs("select id,getId from leads_data where id in ('" . implode("','", $ids) . "')"); 

            foreach($ids as &$id){
                if(isset($data[$id]))   self::$data[$id] = $data[$id];    
                else                    self::$data[$id] = $id;        
            } 
        }
    } 
    
    static public function addCache($leadID, $getID){
        self::$data[$leadID] = $getID;    
    }
    
    static public function get($id, $link = true){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(!$link){
            return self::$data[$id];
        }
        else if($id == self::$data[$id]){
            return "<a style='text-decoration:none;color:#007EBB'>" . self::$data[$id] . "</a>";
        }
        else {                               
            MyZend_Site::addCSS("table/menu.css");
            MyZend_Site::addJS("table/menu.js");
            
            if(T3Users::getCUser()->isRoleAdmin()){
                return "<span 
                    style='color:#007EBB'
                    class='aztable_menu_a'
                    id='aztable_menu_a_{$id}'
                    onmouseover=\"createLeadAdminMenu('{$id}', '" . self::$data[$id] . "');\" 
                ><span>" . self::$data[$id] . "</span><div class='aztable_menu' id='aztable_menu_list_{$id}' ></div></span>";    
            }
            else {
                return "<a style='color:#007EBB' href='/en/account/lead/main/id/" . self::$data[$id] . "'>" . self::$data[$id] . "</a>";   
            }
            
        }
    } 
    
     static public function getLeadID($id, $link = true){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(!$link){
            return self::$data[$id];
        }
        else if($id == self::$data[$id]){
            return self::$data[$id];
        }
        else {                               
         
            
            if(T3Users::getCUser()->isRoleAdmin() || T3Users::getCUser()->isRoleWebmasterAgent()){
                return self::$data[$id];   
            }
            else {
                return "<a style='color:#007EBB' href='/en/account/lead/main/id/" . self::$data[$id] . "'>" . self::$data[$id] . "</a>";   
            }
            
        }
    } 
}