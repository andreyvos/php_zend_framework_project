<?php

class T3Report_States {
    static public function reIndex_V1($dateFrom, $dateTill){
        T3Db::cache()->delete('report_states_v1', "`date` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59'");
        
        $all = T3Db::v1()->fetchAll("select DATE_FORMAT(leaddatetime, '%Y-%m-%d') as `date`, affid as webmaster, `type` as product, state, count(*) as leads, sum(money) as wm, sum(totalmoney) as ttl from stat 
        where leaddatetime BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59' and state!='' group by `date`, webmaster, `product`, state");    

        if(count($all)){
            $idsTemp = array(); 
            foreach($all as &$allEl){ 
                $idsTemp[$allEl['webmaster']] = true;      
            }
            $ids = T3Db::v1()->fetchPairs("select id, t3v2ID from user where id in (" . implode(',', array_keys($idsTemp)) . ") and t3v2ID > 0");  
            
            
            $sold = T3Db::v1()->fetchAll("select DATE_FORMAT(leaddatetime, '%Y-%m-%d') as `date`, affid as webmaster, `type` as product, state, count(*) as sold from stat 
            where leaddatetime BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59' and state!='' and money>0 group by `date`, affid, `type`, state");
            $soldIndex = array(); 
            foreach($sold as &$soldEl){
                $soldEl['product'] = T3Products::oldToNew($soldEl['product']); 
                $soldIndex[$soldEl['date']][$soldEl['webmaster']][$soldEl['product']][$soldEl['state']] = $soldEl['sold'];  
            }
            
            
            $exKeys = null;
            
            foreach($all as $key => &$allEl){
                $allEl['product'] = T3Products::oldToNew($allEl['product']); 
                
                if(isset($ids[$allEl['webmaster']])){           
                    $oldWebmaster = $all[$key]['webmaster'];
                    
                    $allEl['webmaster'] = $ids[$allEl['webmaster']];
                    
                    $allEl['sold'] = ifset($soldIndex[$allEl['date']][$oldWebmaster][$allEl['product']][$allEl['state']], 0);           
                    $allEl['t3'] = round($allEl['ttl'] - $allEl['wm'], 2);
                    
                    $allEl['epl'] = 0;
                    $allEl['alp'] = 0;
                    
                    if($allEl['leads'] > 0) $allEl['epl'] = round(($allEl['wm']/$allEl['leads']) , 2); 
                    if($allEl['sold'] > 0)  $allEl['alp'] = round(($allEl['wm']/$allEl['sold']) , 2);
                    
                    if(is_null($exKeys)) $exKeys = array_keys($allEl);
                }
                else  {
                    unset($all[$key]);
                }          
            }
            
            if(!is_null($exKeys)){
                T3Db::cache()->insertMulty('report_states_v1', $exKeys, $all);
            }
        }

    }
    
    static public function reIndex_V2($dateFrom, $dateTill){
        T3Db::cache()->delete('report_states', "`date` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59'");
        
        $all = T3Db::api()->fetchAll("select DATE_FORMAT(`datetime`, '%Y-%m-%d') as `date`, affid as webmaster, `product` as product, data_state as state, count(*) as leads, sum(wm) as wm, sum(ttl) as ttl from leads_data 
        where `datetime` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59' and data_state!='' and is_test='0' group by `date`, webmaster, `product`, state");    

        if(count($all)){
            
            $sold = T3Db::api()->fetchAll("select DATE_FORMAT(`datetime`, '%Y-%m-%d') as `date`, affid as webmaster, `product` as product, data_state as state, count(*) as sold from leads_data 
            where `datetime` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59' and data_state!='' and wm>0 and is_test='0' group by `date`, webmaster, `product`, state");
            
            $soldIndex = array();
            foreach($sold as &$soldEl){ 
                $soldIndex[$soldEl['date']][$soldEl['webmaster']][$soldEl['product']][$soldEl['state']] = $soldEl['sold']; 
                
            }
            
            $exKeys = null;
            
            foreach($all as $key => &$allEl){
                    $allEl['sold'] = ifset($soldIndex[$allEl['date']][$allEl['webmaster']][$allEl['product']][$allEl['state']], 0);           
                    $allEl['t3'] = round($allEl['ttl'] - $allEl['wm'], 2);
                    
                    $allEl['epl'] = 0;
                    $allEl['alp'] = 0;
                    
                    if($allEl['leads'] > 0) $allEl['epl'] = round(($allEl['wm']/$allEl['leads']) , 2); 
                    if($allEl['sold'] > 0)  $allEl['alp'] = round(($allEl['wm']/$allEl['sold']) , 2);
                    
                    if(is_null($exKeys)) $exKeys = array_keys($allEl);
                         
            }
            
            if(!is_null($exKeys)){
                T3Db::cache()->insertMulty('report_states', $exKeys, $all);
            }
        }

    }    
}

