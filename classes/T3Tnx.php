<?php

class T3Tnx {
    static public function createSecureID($id){
        return "{$id}-" . AP_Math_BaseConverting::dec_to_X(crc32($id . "secureTNXID"), 62) . "-" . AP_Math_BaseConverting::dec_to_X(crc32($id . "secureTNXID2"), 62);
    }
    
    static public function decodeSecureID($code){
        $a = explode("-", $code);
        if(count($a) == 3){
            $id = (int)$a[0];
            
            if(
                $id > 0 && 
                AP_Math_BaseConverting::dec_to_X(crc32($id . "secureTNXID"), 62) == $a[1] &&
                AP_Math_BaseConverting::dec_to_X(crc32($id . "secureTNXID2"), 62) == $a[2] 
            ){
                return $id;
            }
        }   
        return null;
    }
    
    static public function addURL($url){
        $url = (string)$url;
        $urlID = T3Db::api()->fetchOne("select id from tnx_links_base where url=?", $url);
        
        if(!$urlID){
            try{
                T3Db::api()->insert("tnx_links_base", array(
                    'url' => $url,
                ));
                $urlID = T3Db::api()->lastInsertId(); 
            }
            catch(Exception $e){
                return true;
            }   
        }
        
        T3Db::api()->insert("tnx_links_log", array(
            'link_id'       => $urlID,
            'click_date'    => date("Y-m-d H:i:s"),
            'ip'            => myHttp::get_ip_num(), 
        ));
    } 
    
    static public function addID($id){
        if(is_numeric($id) && $id){
            T3Db::api()->insert("tnx_links_log", array(
                'link_id'       => $id,
                'click_date'    => date("Y-m-d H:i:s"),
                'ip'            => myHttp::get_ip_num(), 
            ));        
        }
    } 
    
    static protected function getArrayTemplate($linkID, $date){
        return array(
            'date'      => $date, 
            'link_id'   => $linkID, 
            'clicks'    => 0,
            'solds'     => 0,
            'earnings'  => 0,
            'epc'       => 0,
        );    
    }
    
    static public function reindexHourly($date, $hour){  
        $hour_02d = sprintf("%02d", $hour);
        self::reindexAbstract("tnx_stat_hourly", "{$date} {$hour_02d}:00:00", "{$date} {$hour_02d}:00:00", "{$date} {$hour_02d}:59:59");  
    }
    
    static public function reindexDaily($date){
        self::reindexAbstract("tnx_stat_daily", $date, "{$date} 00:00:00", "{$date} 23:59:59");  
    }
    
    static protected function reindexAbstract($table, $date, $dateFrom, $dateTill){
        T3Db::api()->delete($table, "`date`='{$date}'");
        
        $clicks = T3Db::api()->fetchAll("select link_id, count(*) as clicks from tnx_links_log where click_date BETWEEN '{$dateFrom}' and '{$dateTill}' group by link_id");
        $solds = T3Db::api()->fetchAll("select link_id, count(*) as solds, sum(`value`) as `sum` from tnx_solds where `datetime` BETWEEN '{$dateFrom}' and '{$dateTill}' group by link_id");
        
        $index = array();
        
        if(count($clicks)){
            foreach($clicks as $click){
                if(!isset($index[$click['link_id']])) $index[$click['link_id']] = self::getArrayTemplate($click['link_id'], $date);
                $index[$click['link_id']]['clicks'] = $click['clicks'];
            }    
        }
        
        if(count($solds)){
            foreach($solds as $sold){
                if(!isset($index[$sold['link_id']])) $index[$sold['link_id']] = self::getArrayTemplate($sold['link_id'], $date);
                
                $index[$sold['link_id']]['solds'] = $sold['solds'];
                $index[$sold['link_id']]['earnings'] = $sold['sum'];
                
                if($index[$sold['link_id']]['clicks']){
                    $index[$sold['link_id']]['epc'] = round($index[$sold['link_id']]['earnings'] / $index[$sold['link_id']]['clicks'], 2);
                }
            }    
        }
        
        if(count($index)){
            T3Db::api()->insertMulty($table, array_keys(self::getArrayTemplate(1,1)), $index);
        }    
    }
    
    
    static public function getUrlsForZendForm(){
        return T3Db::api()->fetchPairs("select id,LEFT(url,80) from tnx_links_base");    
    }
    
    /**
    * 
    * @param T3Lead $lead
    * @return T3Tnx_Link
    */
    static public function getTnxObjectByLead(T3Lead $lead){
        $url = (string)T3Db::api()->fetchOne("select url from tnx_rules where (product='*' or product=?) and (webmaster='0' or webmaster=?) order by webmaster desc, product desc limit 1", array(
            $lead->product,
            $lead->affid
        ));
        
        $url = str_replace(array("\r\n", "\r"), array("\n", "\n"), $url);
        $url = trim($url);
        $urlNotParams = '';
        
        if($url != ''){
            $a = explode("\n", $url);
            
            if(count($a) > 1){
                $all = 0;
                $indexTable = array();
                foreach($a as $e){
                    $ves = (int)$e;
                    if($ves < 1)$ves = 1;
                    $all+= $ves;   
                    
                    $str = explode(" ", $e, 2);
                    if(count($str) == 2 && is_numeric($str[0])) $link = trim($str[1]);
                    else $link = trim($e);
                    
                    $indexTable[] = array(
                        'num' => $ves,
                        'url' => $link,
                    );
                } 
                
                $rand = rand(1, $all);
                
                $start = 1;
                foreach($indexTable as $e){
                    $fin = $start + $e['num']; 
                    if($rand >= $start && $rand < $fin){
                        $returnLink = $e['url'];
                        break;
                    }
                    $start = $fin;
                }
                
                $url = $returnLink;
                 
            }
            else {
                $str = explode(" ", $url, 2);
                if(count($str) == 2 && is_numeric($str[0])) $url = trim($str[1]);  
            }
            
            $urlNotParams = $url;
            $params = $lead->getBody()->getParams();
            
            $newURL = '';
            
            
            /**
            * 0 - поиск начала переменной
            * 1 - внутри скобок
            * 
            * @var int
            */
            $type = 0;
            $currentValue = array('');
            
            for($i = 0; $i < strlen($url); $i++){
                $symb = substr($url, $i, 1);
                if($type == 0){
                    if($symb == "{"){
                        $type = 1;           
                    }
                    else {
                        $newURL.= $symb;        
                    }
                } 
                else if($type == 1){
                    if($symb == "}"){
                        if(count($currentValue) == 1){
                            // Добавить переменную из тела лида
                            $vName = $currentValue[0];
                            if ($vName == 'subacc'){
                                $newURL.= urlencode($lead->subacc_str);        
                            }
                            else if ($vName == 'ssid'){
                                $newURL.= urlencode($lead->getSecureSubID(T3BuyerChannels::getChannel(11209))); // Специальный канал для всех TNX        
                            }
                            else if ($vName == 'leadid'){
                                $newURL.= $lead->id; // Специальный канал для всех TNX        
                            }
                            else if(isset($lead->getBody()->$vName) && !is_object($lead->getBody()->$vName)){
                                $newURL.= urlencode($lead->getBody()->$vName);    
                            }    
                        }
                        else if(count($currentValue) == 2){
                            if($currentValue[0] == 'phone1'){
                                $vName = $currentValue[1];  
                                if(isset($lead->getBody()->$vName) && !is_object($lead->getBody()->$vName)){
                                    $newURL.= substr($lead->getBody()->$vName, 0 , 3);    
                                }
                            }
                            else if($currentValue[0] == 'phone2'){
                                $vName = $currentValue[1];  
                                if(isset($lead->getBody()->$vName) && !is_object($lead->getBody()->$vName)){
                                    $newURL.= substr($lead->getBody()->$vName, 3 , 3);    
                                }
                            }
                            else if($currentValue[0] == 'phone3'){
                                $vName = $currentValue[1];  
                                if(isset($lead->getBody()->$vName) && !is_object($lead->getBody()->$vName)){
                                    $newURL.= substr($lead->getBody()->$vName, 6 , 4);    
                                }
                            }
                            else if($currentValue[0] == 'search_string'){
                                $search_string = trim( T3Db::api()->fetchOne("select keyword from leads_visitors where lead_id=?", $lead->id) );
                                if($search_string == '') $search_string = $currentValue[1];
                                
                                $newURL.= urlencode($search_string);    
                            }                                                                           
                            else if($currentValue[0] == 'lead'){
                                if($currentValue[1] == 'webmaster_id'){
                                    $newURL.= $lead->affid;    
                                }   
                            }       
                        }      
                        
                        $type = 0; 
                        $currentValue = array('');       
                    }
                    else if($symb == ":"){
                        $currentValue[count($currentValue)] = '';           
                    }
                    else {
                        $currentValue[count($currentValue)-1].= $symb;        
                    }
                }              
            }
            
            $url = $newURL;
            
            /*
            if(count($params)){
                // Замена макросами информации из тела лида
                foreach($params as $k => $v){
                    $url = str_replace('{' . $k . '}', urlencode($v), $url);   
                }
                
                //$url = str_replace('{visitor:keyword}', urlencode(trim(T3Db::api()->fetchOne("select keyword from leads_visitors where lead_id=?", $lead->id))), $url);  
                
                // Обрезание ненайденных макросов
                $url = preg_replace("/{(?:(?:[a-z0-9_])*)}/i", '', $url);
            } 
            */       
        }
        
        /*
        if($url != ''){
            $url = self::createRedirectURL($url);    
        }
        */
        
        
        return self::getLinkObjectByURL($url, $urlNotParams);    
    }
    
    
    static public function getLinkObjectByURL($url, $urlNotParams){
        $urlNotParams = trim($urlNotParams);
        $all = array();
        if($urlNotParams != ''){
            $all = T3Db::api()->fetchRow("select * from tnx_links_base where url=?", $urlNotParams);
            if($all === false){
                T3Db::api()->insert("tnx_links_base", array(
                    'url' => $urlNotParams,
                ));
                $all = T3Db::api()->fetchRow("select * from tnx_links_base where id=?", T3Db::api()->lastInsertId());
            }
        }
        
        $obj = new T3Tnx_Link();
        
        if(is_array($all) && count($all)){
            foreach($all as $k => $v){
                $obj->$k = $v;
            }   
        }
        
        $obj->urlIncludeParams = $url;
        
        return $obj; 
    }
    
    
}