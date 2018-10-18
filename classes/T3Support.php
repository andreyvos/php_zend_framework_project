<?php

class T3Support {
    static public function getWorkers($group = null){
        $result = array();
        
        $select = T3Db::api()->select()
        ->from("support", "*")
        ->join("support_groups", "support.type = support_groups.id", array(
            "groupTitle" => "title"
        ))
        ->order(array(
            "support_groups.order",
            "support.order"
        ));
        
        if(!is_null($group) && is_numeric($group) && $group){
            $select->where("support.type=?", $group);
        }
        
        
        $data = T3Db::api()->fetchAll($select);
        
        if(count($data)){
            foreach($data as $el){
                $man = new T3Support_Man();
                foreach($el as $k=> $v){
                    $man->$k = $v;    
                }
                
                $result[] = $man;    
            }
        }
        
        return $result;
    } 
    
    static public function getWorkersMerchantSolutions(){
        return self::getWorkers(2);
    }
    
    static public function getWorkersAffiliateSolutions(){
        return self::getWorkers(1);
    }   
}