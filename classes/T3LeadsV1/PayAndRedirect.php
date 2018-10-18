<?php

class T3LeadsV1_PayAndRedirect {
    static protected $data = array();
    
    static public function load($leadsIds){
        $ids = array();
        
        if(!is_array($leadsIds))$leadsIds = array($leadsIds);
        
        if(count($leadsIds)){
            foreach($leadsIds as $id){
                if(is_numeric($id) && !isset(self::$data[$id])){
                    $ids[] = $id; 
                    self::$data[$id] = array();      
                }
            }
            
            if(count($ids)){
                $result = T3Db::v1()->fetchAll("select idlead,sold,createRedirect,goodRedirect from stat_sold_redirect_cache where idlead in (" . implode(",", $ids) . ")");
                
                if(count($result)){
                    foreach($result as $res){
                        $res['sold'] =  substr($res['sold'],0,strlen($res['sold'])-1);
                        if(strlen($res['sold'])){
                            $soldArray = array_unique(explode(',', $res['sold'])); 
                            $createArray = array_unique(explode(',', $res['createRedirect'])); 
                            $goodArray = array_unique(explode(',', $res['goodRedirect']));
                            
                            foreach($soldArray as $el){
                                if(in_array($el, $goodArray)){
                                    self::$data[$res['idlead']][$el] = "good";   
                                }
                                else if(in_array($el, $createArray)){
                                    self::$data[$res['idlead']][$el] = "create"; 
                                }
                                else {
                                    self::$data[$res['idlead']][$el] = "sold"; 
                                }        
                            }    
                        }
                    }    
                }
            }
             
        }   
    }
    
    
    static public function getData($leadID){
        self::load($leadID);
        return self::$data[$leadID];   
    }
    
    static public function render($leadID, $full = true){
        $result = self::getData($leadID);
        
        
        if(count($result)){
            if(!$full){
                $create = 0;
                $good = 0;
                foreach($result as $buyer => $type){
                    if($type == 'good'){
                        $good++;   
                    }
                    else if($type == 'create'){
                        $create++; 
                    }    
                }
                
                
                
                if($create || $good){
                    if($create > 0){
                        if($good + $create == 1){
                            return "<b style='color:#F30'>No</b>";    
                        }
                        else {
                            return "<b style='color:#F30'>No:</b> <b>" . ($good + $create) . "</b> / <span style='color:#F30'>{$good}</span>";
                        }
                    }
                    else {
                        return "<b style='color:#090'>Yes</b>";
                        //return "<b style='color:#090'>{$good}</b>"; 
                    }        
                }
                else {
                    return "<span style='color:#CCC'>-</span>";    
                }
            }
            else {
                $buyers = array();
                foreach($result as $buyer => $type){
                    if($type == 'good'){
                        $buyers[] = "<span style='color:#090'>{$buyer}</span>";    
                    }
                    else if($type == 'create'){
                        $buyers[] = "<span style='color:#F30'>{$buyer}</span>";  
                    }
                    else if($type == 'sold'){
                        $buyers[] = "<span style='color:#888'>{$buyer}</span>";  
                    }    
                }
                return implode(", ", $buyers);
            }
        }
        else {
            return "<span style='color:#CCC'>-</span>";
        } 
           
    }
    
    
    static protected function add_Abstract($type, $leadID, $buyerName){
        $leadID = (int)$leadID;
        
        if($buyerName != 'RevNetReject'){
            try {
                database::getConnect()->insert("stat_sold_redirect_cache",array( 
                    'idlead' => $leadID,
                    $type    => "{$buyerName},",        
                ));
            }
            catch(Exception $e){
                database::getConnect()->update("stat_sold_redirect_cache", array(
                    $type => new Zend_Db_Expr("CONCAT(`{$type}`, " . database::getConnect()->quote($buyerName) . ", ',')")
                ), "idlead={$leadID}");
            } 
        }   
    }
    
    static public function add_Sold($leadID, $buyerName){
        self::add_Abstract("sold", $leadID, $buyerName);    
    }
    
    static public function add_createRedirect($leadID, $buyerName){
        self::add_Abstract("createRedirect", $leadID, $buyerName);    
    }
    
    static public function add_goodRedirect($leadID, $buyerName){
        self::add_Abstract("goodRedirect", $leadID, $buyerName);    
    }    
}