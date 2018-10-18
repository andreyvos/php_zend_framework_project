<?php

class T3Cache_Reasons {
    static protected $defaultRejectReasonsIds = array(14118);
    
    static $leadsCache = array();
    static $rejectReasonsCache = array();
    
    /**
    * Массовая загрузка информации
    * 
    * Для загрузки иформации о коментариях, нужна информация о лидах:
    * 1. статус
    * 2. цена вебмастера
    * 3. id лида
    * 
    * @param array $leadsInfo
    */
    static public function load($leadsInfo){
        $newReject = array();
        $newError  = array();
        
        //varExport($leadsInfo);
        
        if(count($leadsInfo)){
            foreach($leadsInfo as $inf){
                if(isset($inf['id']) && isset($inf['status']) && isset($inf['wm'])){
                    if(!isset(self::$leadsCache[$inf['id']])){
                        if($inf['status'] == 'error'){
                            $newError[T3Lead_Logs::getTableName($inf['datetime'])][] = $inf['id'];     
                        }
                        else if(($inf['status'] == 'reject' || $inf['status'] == 'sold') && $inf['wm'] == 0){
                            $newReject[] = $inf['id'];   
                        }
                        else {
                            self::$leadsCache[$inf['id']] = array();   
                        }     
                    }
                }    
            }
        } 
        
        
        if(count($newError)){
            foreach($newError as $tableLogName => $errorsLeadsIDS){
                try{
                    $all = T3Db::apiReplicant()->fetchAll("select leadID, descriptions from `{$tableLogName}` where leadID in (" . implode(",", $errorsLeadsIDS) . ")");
                    $leadsIndex = array();
                    
                    
                    
                    if(count($all)){
                        foreach($all as $el){
                            $descrs = @unserialize($el['descriptions']);
                            if(is_array($descrs) && count($descrs)){
                                if(!isset($leadsIndex[$el['leadID']])) $leadsIndex[$el['leadID']] = array();  
                                foreach($descrs as $descr){
                                    $leadsIndex[$el['leadID']][sprintf("%u", crc32($descr))] =  $descr;       
                                }    
                            } 
                        }
                    } 
                    
                    
                    foreach($errorsLeadsIDS as $id){ 
                        if(isset($leadsIndex[$id])){
                            self::$leadsCache[$id] = array_values($leadsIndex[$id]);     
                        }
                        else {
                            self::$leadsCache[$id] = array(); 
                        }   
                    }     
                }   
                catch(Exception $e){
                    
                } 
            }
            
            
               
        }
        
        if(count($newReject)){
            $all = T3Db::apiReplicant()->fetchAll("select lead_id,reason_id from buyer_channels_reasons_log where lead_id in (" . implode(",", $newReject) . ")"); 
            $indexData = array();
            $indexReasonsID = self::$defaultRejectReasonsIds;
            
            if(count($all)){
                foreach($all as $el){
                    if(!isset($indexData[$el['lead_id']]))$indexData[$el['lead_id']] = array();
                    $indexData[$el['lead_id']][$el['reason_id']] = $el['reason_id']; 
                    
                    $indexReasonsID[$el['reason_id']] = $el['reason_id'];           
                }
            }  
            
            if(count($indexReasonsID)) self::loadRejectReasons($indexReasonsID); 
            
            foreach($newReject as $id){
                $reasonsStrings = array();
                if(isset($indexData[$id]) && is_array($indexData[$id]) && count($indexData[$id])){
                    $reasonsIds = self::$defaultRejectReasonsIds;
                    foreach($indexData[$id] as $rId){
                        if(!in_array($rId, $reasonsIds)){
                            $reasonsIds[] = $rId;     
                        }
                    }   
                }
                else {
                    $reasonsIds = self::$defaultRejectReasonsIds;
                } 
                
                if(count($reasonsIds)){
                    foreach($reasonsIds as $rId){
                        $rStr = self::getRejectReason($rId);
                        if(strlen($rStr)){
                            $reasonsStrings[] = $rStr;
                        }    
                    }
                }
                self::$leadsCache[$id] = $reasonsStrings;   
            }
        }  
        
        //varExport(self::$leadsCache); 
    }
    
    
    static public function loadRejectReasons($ids){
        $new = array();
        if(count($ids)){
            foreach($ids as $id){
                if(is_numeric($id)){
                    if(!isset(self::$rejectReasonsCache[$id])){
                        $new[] = $id;    
                    }
                }
            }    
        }
        
        if(count($new)){
            $index = T3Db::apiReplicant()->fetchPairs("select id,reason from buyer_channels_reasons_base where id in (" . implode(",", $new) . ") and isShowInWm=1");
            foreach($new as $el){
                self::$rejectReasonsCache[$el] = ifset($index[$el]); 
            }   
        }
    }  
    
    static public function getRejectReason($id){
        if(!isset(self::$rejectReasonsCache[$id])) self::loadRejectReasons(array($id));
        return ifset(self::$rejectReasonsCache[$id]);
    }  
    
    static public function renderReasonsForTable($leadId){
        $reason = '';
        if(isset(self::$leadsCache[$leadId]) && is_array(self::$leadsCache[$leadId]) && count(self::$leadsCache[$leadId])){
            $reason = "<nobr>" . implode("</nobr><br><nobr>", self::$leadsCache[$leadId]) . "</nobr>";     
        } 
        return $reason;  
    }
}