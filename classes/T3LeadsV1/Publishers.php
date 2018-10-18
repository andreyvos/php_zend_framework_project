<?php

class T3LeadsV1_Publishers {
    static protected $webmasters = array();
    
    static public function cache(array $ids){
        $ids = array_unique($ids);
        $dataTemp = T3Db::v1()->fetchAll("select id,`login`,t3v2ID from `user` where id in ('" . implode("','", $ids) . "')"); 
        $data = array();
        foreach($dataTemp as $el){
            $data[$el['id']] = $el;    
        }
        
        foreach($ids as &$id){
            
            if(isset($data[$id])){
                self::$webmasters[$id] = $data[$id];    
            }
            else {
                self::$webmasters[$id] = array(
                    'id' => $id,
                    'login' => "Unknown: {$id}",
                    't3v2ID' => '0', 
                );    
            }
        }   
    }
    
    static public function renderWebmaster($id, $link = true){
        if(!isset(self::$webmasters[$id])) self::cache(array($id)); 
        
        $wm = self::$webmasters[$id];
        
        if(!$link) return $wm['login'];
        return "<a href='/en/account/webmasters/main/id/{$wm['t3v2ID']}' target='_blank'>{$wm['login']}</a>";
    }    
}