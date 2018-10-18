<?php

class T3SubaccountReport {

    public static function getListOfSubaccounts($webmaster_id) {
        $r =  T3Db::apiReplicant()->query('select * from users_company_webmaster_subacc where idcompany=? LIMIT 100', array($webmaster_id))->fetchAll();
        if(is_array($r))
        {
            $ids = array();
            foreach($r as $oneR)
            {
                $ids[] = $oneR["id"];
            }
            T3Cache_PublisherSubaccount::load($ids);
            for($j=0;$j<count($r);$j++)
            {
                $r[$j]["name"] = T3Cache_PublisherSubaccount::get($r[$j]['id']);
            }
            
        }
        return $r;  
    }
    
    public static function getSubAccountsCount($webmaster_id) {
        return T3Db::apiReplicant()->query('select COUNT(*) from users_company_webmaster_subacc where idcompany=?', array($webmaster_id))->fetchColumn();
    }

    public static function getSubaccountsSummary($subaccount_id, $startDate, $endDate) {

        $r = T3Db::apiReplicant()->fetchAll('

          select 
            id ,SUM(summ) as money, SUM(clicks) as clicks, SUM(leads_count) as leads_count,
            SUM(sold_leads_count) as sold_leads_count
          from subaccount_summary   
          where
            (webmaster_id=?) and
            (date >= ?) AND
            (date <= ?)
            
          group by  id order by SUM(summ) desc limit 1000

        ', array($subaccount_id, mySqlDateFormat(strtotime($startDate)), mySqlDateFormat(strtotime($endDate))));
        
        if(is_array($r)){

            $ids = array();
            foreach($r as $oneR){
                 $ids[] = $oneR['id'];
            }
            
            T3Cache_PublisherSubaccount::load($ids);
            
            for($j=0;$j<count($r);$j++){
                $r[$j]["name"] = T3Cache_PublisherSubaccount::get($r[$j]['id']);
            }
                        
        }
         
        return $r;
    }
    
    public static function  getDataFromSubaccount($subaccount_id, $startDate, $endDate) {
        $r = T3Db::apiReplicant()->query(
            'select * from subaccount_summary where (id=?) and (date >= str_to_date(?, "%d.%m.%Y")) AND (date <= str_to_date(?, "%d.%m.%Y") ) order by date ', 
            array(
                $subaccount_id,
                date("d.m.Y", strtotime($startDate)),
                date("d.m.Y", strtotime($endDate)),
            )
        )->fetchAll();
        
        if(is_array($r) && count($r)){
            $name =  T3Cache_PublisherSubaccount::get($r[0]['id']);
        }
        
        for($j=0;$j<count($r);$j++){
            $r[$j]['name'] = $name;
        }                          
        
        return $r;
    }

    static public function addClick(T3Channel_JsFormUniqueClient $click){
        $subId = T3WebmasterCompany::getSubaccountID($click->webmaster, $click->subaccount);
        $date = substr($click->date, 0, 10);
        
        $id = T3Db::api()->fetchOne("select id from subaccount_summary where `date`=? and id=?", array($date, $subId)); 
        
        
        if($id){
            T3Db::api()->update("subaccount_summary", array(
                'clicks' => new Zend_Db_Expr("clicks+1")
            ), "id='" . $id . "' and date='" . $date . "'");   
        }
        else {
            try{
                T3Db::api()->insert("subaccount_summary", array(
                    'id'            => $subId,
                    //'name'          => $click->subaccount,
                    'webmaster_id'  => $click->webmaster,   
                    'date'          => $date,                     
                    'clicks'        => 1,   
                ));      
            } 
            catch(Exception $e){}
        }
    }
    
    public static function updateCacheLead($channelid, $date,
            $allLeadsCount, $soldLeadsCount, $summ) {
        // в ситуации когда лид продан или не продан
        // T3System::getConnectCache()
        $db = T3Db::api();

        // select
        $res = $db->fetchOne(
                "select ai_id from subaccount_summary where ".
                "(id=?) and (date=?)",
                array($channelid, $date)
        );

        if ($res) {
            // Update
            $db->query(
                    "update subaccount_summary set ".
                    "sold_leads_count=sold_leads_count+".$soldLeadsCount.", summ=summ+".$summ.", leads_count=leads_count+".$allLeadsCount." ".
                    "where ai_id=$res"
            );
        }
        else {
            // если такой строки не существует, то создаем и получаем два
            // дополнительных значения из таблицы users_company_webmaster_subacc
            // webmaster_id, name
            //

            $subacc = $db->fetchAll("select * from users_company_webmaster_subacc where (id=?)", array($channelid));
            $db->insert("subaccount_summary",
                    array(
                    "id" => $channelid,
                    //"name" => $subacc[0]['name'],
                    "webmaster_id" => $subacc[0]['idcompany'],
                    "date" => $date,
                    "leads_count" => $allLeadsCount,
                    "sold_leads_count" => $soldLeadsCount,
                    "summ" => $summ
                    )
            );
        }

    }

}