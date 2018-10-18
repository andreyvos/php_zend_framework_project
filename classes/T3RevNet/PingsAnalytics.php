<?php

class T3RevNet_PingsAnalytics {
    static public function getSoldLeads($date){
        return T3Db::api()->fetchAll("select id,getId,data_email,data_phone,data_ssn from leads_data where affid='26918' and TO_DAYS(`datetime`) = TO_DAYS(?) and wm>0", $date);
    }
    
    static public function indexDate($date){
        // нахожу все проданные ревнетом лиды за сегодняшний день
        $solds = T3Db::api()->fetchAll("select id,getId,data_email,data_phone,data_ssn, wm, `datetime` from leads_data where affid='26918' and TO_DAYS(`datetime`) = TO_DAYS(?) and wm>0", $date);
        
        if(count($solds)){
            $emails = array();
            $indexLeadId = array();
            $indexWMErn = array();
            $indexDatetime = array();
            foreach($solds as $el){
                if(!in_array($el['data_email'], $emails)){
                    $emails[] = $el['data_email'];  
                    $indexLeadId[strtolower($el['data_email'])] = array(
                        'id'    => $el['id'],
                        'getId' => $el['getId'],
                    );
                    
                    $indexWMErn[strtolower($el['data_email'])] = $el['wm']; 
                    $indexDatetime[strtolower($el['data_email'])] = $el['datetime']; 
                     
                }
            }
            
            // Находим посылали ли мы их ревнету
            $rLeads = T3Db::api()->fetchAll("select id as rlead_id, lead_id, data_email, create_date, webmaster, account from revnet_leads where `create_date` BETWEEN '2010-05-01 00:00:00' and '{$date} 23:59:59' and data_email in ('" . implode("','", $emails) . "')");
            
            // Находим все пинги
            $rPings = T3Db::api()->fetchPairs("select firstLeadId, create_date from revnet_track where TO_DAYS(create_date)=TO_DAYS(?) and `type`='t3Sold'", $date);
            
            $summary = array(
                'date'  => $date,
                'all_leads' => count($rLeads),   
                'all_pings' => count($rPings),   
                'good_pings' => 0,
                'not_pings'  => 0,
            );
            
            if(count($rLeads)){
                $indexLeads = array();
                foreach($rLeads as $k => $lead){
                    if(!in_array($lead['lead_id'], $indexLeads)){
                        $indexLeads[] = $lead['lead_id'];
                    }    
                }
                
                $leadsDates = T3Db::api()->fetchPairs("select id,`datetime` from leads_data where id in (" . implode(",", $indexLeads) . ")");
                
                foreach($rLeads as $k => $lead){
                    $rLeads[$k]['date']                 = $date; 
                    $rLeads[$k]['revnet_lead_id']       = ifset($indexLeadId[strtolower($lead['data_email'])]['id']); 
                    $rLeads[$k]['revnet_lead_getid']    = ifset($indexLeadId[strtolower($lead['data_email'])]['getId']);  
                    $rLeads[$k]['wm']                   = ifset($indexWMErn[strtolower($lead['data_email'])], 0);
                    $rLeads[$k]['rLeadDate']            = ifset($indexDatetime[strtolower($lead['data_email'])]);  
                    $rLeads[$k]['lead_datetime']        = ifset($leadsDates[$lead['lead_id']]); 

                    $rLeadDateTS = strtotime($rLeads[$k]['rLeadDate']);
                    $create_dateTS = strtotime($rLeads[$k]['create_date']);
                    
                    if(($rLeadDateTS - $create_dateTS) > 0){
                        if(isset($rPings[$lead['lead_id']])){
                            $rLeads[$k]['ping'] = 1;
                            $rLeads[$k]['pingDate'] = $rPings[$lead['lead_id']];
                            $summary['good_pings']++;   
                        }
                        else {
                            $rLeads[$k]['ping'] = 0;
                            $summary['not_pings']++; 
                            $rLeads[$k]['pingDate'] = null;
                        }
                    }
                    else {
                        unset($rLeads[$k]);
                    }    
                }
            } 
            
            T3Db::api()->delete("revnet_pings_analytics_details", "`date`='{$date}'");  
            T3Db::api()->delete("revnet_pings_analytics_summary", "`date`='{$date}'"); 
            
            T3Db::api()->insert("revnet_pings_analytics_summary", $summary);
            if(count($rLeads)){
                $rLeads = array_values($rLeads);
                T3Db::api()->insertMulty("revnet_pings_analytics_details", array_keys($rLeads[0]), $rLeads);
            }
        }
        
        $result = $summary;
        
        $result['percent'] = 100;
        if($result['good_pings'] || $result['not_pings']){
            $result['percent'] = round(($result['good_pings']*100)/($result['good_pings']+$result['not_pings']), 1);    
        }
        
        return $result; 
    }    
}