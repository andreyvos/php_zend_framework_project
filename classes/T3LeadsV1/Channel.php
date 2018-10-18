<?php

class T3LeadsV1_Channel {
    static protected $feedsTitles = array();
    
    static public function cacheChannels(array $ids){
        $ids = array_unique($ids);
        $idsQuery = array();
        foreach($ids as &$id){
            $idsQuery[] = $id;
            if(strlen($id)<4){
                $idsQuery[] = sprintf("%03d", (int)$id);
                $idsQuery[] = sprintf("%04d", (int)$id);
            }
        }
        
        $dataTemp = T3Db::v1()->fetchAll("select prfidid+0 as id, prfidurl, `type`  from prfids where prfidid in ('" . implode("','", $idsQuery) . "')"); 
        
        $data = array();
        if(count($dataTemp)){
            foreach($dataTemp as $el){
                $data[$el['id']] = $el;   
            }
        }
        
        foreach($ids as &$id){
            $id = $id + 0;
            if(isset($data[$id])){
                self::$feedsTitles[$id] = $data[$id];    
            }
            else {
                self::$feedsTitles[$id] = array(
                    'prfidid' => $id,
                    'prfidurl' => "Unknown: {$id}",
                    'type' => 'feed', 
                );  
            }
        }   
    }
    
    static public function renderChannelTitle($id){
        if(!isset(self::$feedsTitles[$id])) self::cacheChannels(array($id)); 
        
        $feedUrl = self::$feedsTitles[$id]['prfidurl'];
        
        if(self::$feedsTitles[$id]['type'] == 'posting')    return "<span style='color:#5A5'>Post</span>: <span style='color:#333'>{$feedUrl}</span>"; 
        else                                                return "<span style='color:#44E'>Form</span>: <a href='http://{$feedUrl}' target='_blank'>{$feedUrl}</a>";  
    }          
}