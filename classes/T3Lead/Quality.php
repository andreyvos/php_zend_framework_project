<?php

class T3Lead_Quality {
    static public function add(T3Lead $lead, $isFroud = false, $froudDetails = ''){
        try{
            if ($lead->product == 'call' || $lead->product == 'callmortgage' || $lead->product == 'callpersonalloan' || $lead->product == 'callautotitle'){
                T3Db::api()->insert("leads_quality", array(
                    'id'                        =>  $lead->id,
                    'create'                    =>  $lead->datetime,
                    'product'                   =>  $lead->product,
                    'complite'                  =>  '0',
                    'email'                     =>  $lead->data_phone,
                    'ssn'                       =>  $lead->data_ssn,
                    'duplicateCount'            =>  '0',
                    'duplicateDitails'          =>  '',
                    'isFroud'                   =>  (int)(bool)$isFroud,
                    'froudDetails'              =>  $froudDetails,
                    'isCurrentDuplicate'        =>  '0',
                    'currentDuplicateDetails'   =>  '',
                ));
            }else if ($lead->product == 'callukpayday'){
                T3Db::api()->insert("leads_quality", array(
                    'id'                        =>  $lead->id,
                    'create'                    =>  $lead->datetime,
                    'product'                   =>  $lead->product,
                    'complite'                  =>  '0',
                    'email'                     =>  $lead->body->phone,
                    'ssn'                       =>  $lead->data_ssn,
                    'duplicateCount'            =>  '0',
                    'duplicateDitails'          =>  '',
                    'isFroud'                   =>  (int)(bool)$isFroud,
                    'froudDetails'              =>  $froudDetails,
                    'isCurrentDuplicate'        =>  '0',
                    'currentDuplicateDetails'   =>  '',
                ));
            }else{
                T3Db::api()->insert("leads_quality", array(
                    'id'                        =>  $lead->id,
                    'create'                    =>  $lead->datetime,
                    'product'                   =>  $lead->product,
                    'complite'                  =>  '0',
                    'email'                     =>  $lead->data_email,
                    'ssn'                       =>  $lead->data_ssn,
                    'duplicateCount'            =>  '0',
                    'duplicateDitails'          =>  '',
                    'isFroud'                   =>  (int)(bool)$isFroud,
                    'froudDetails'              =>  $froudDetails,
                    'isCurrentDuplicate'        =>  '0',
                    'currentDuplicateDetails'   =>  '',
                ));
            }
        }
        catch (Exception $e) {}
    }

    static public function run($limit = 100, $order = 'asc'){
        $all = T3Db::api()->fetchAll("select `id`, `create`, `product`, `email`, `ssn` from leads_quality where `complite`='0' order by id {$order} limit {$limit}");

        if(count($all)){
            $ids = array();
            foreach($all as $el){
                $ids[] = $el['id'];
            }

            T3Db::api()->update("leads_quality", array(
                'complite' => '2',
            ), "`id` in (" . implode(",", $ids) . ")");
            
            foreach($all as $el){
                $startTime = microtime(1);

                $updateArray = array(
                    'complite'                  =>  '1',
                    'duplicateCount'            =>  '0',
                    'duplicateDitails'          =>  array(),
                    'isCurrentDuplicate'        =>  '0',
                    'currentDuplicateDetails'   =>  array(),
                    'runTime'                   =>  0,
                );

                // Поиск лидов за последний месяц
                $table_type     = 'email';
                $table_value    = 'email';
                $lead_value     = 'email';

                if($el['product'] == 'payday'){
                    $table_type     = 'ssn';
                    $table_value    = 'ssn';
                    $lead_value     = 'ssn';
                }
                
                if($el['product'] == 'call' || $el['product'] == 'callukpayday' || $el['product'] == 'callmortgage' || $el['product'] == 'callpersonalloan' || $el['product'] == 'callautotitle'){
                    $table_type     = 'phone';
                    $table_value    = 'phone';
                    $lead_value     = 'email';
                }

                


                // Дублирующиеся лиды за месяц
                $dupMonIds = T3Db::api()->fetchCol(
                    "select lead from buyer_channels_dup_{$table_type}_global where `{$table_value}`=? and `product`=? and `lead` < ? and `date`>?", array(
                        $el[$lead_value],
                        T3Products::getID($el['product']),
                        $el['id'],
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 30, date("Y")))
                    )
                );

                if(count($dupMonIds)){
                    $dupMonDetails = T3Db::api()->fetchAll("select id, `datetime`, affid, agentID from leads_data where id in (" . implode(",", $dupMonIds) . ")");

                    $updateArray['duplicateCount'] = count($dupMonDetails);

                    if(count($dupMonDetails)){
                        $updateArray['duplicateDitails'] = $dupMonDetails;

                        // Если есть лиды за месяц, то проверяем в них, если ли среди них лиды за последние 2 мин
                        $checkPeriod = 120; // 2 min

                        $leadTime = strtotime($el['create']);

                        foreach($dupMonDetails as $dupEl){
                            $dupTime = strtotime($dupEl['datetime']);

                            if($leadTime - $dupTime < $checkPeriod){
                                $updateArray['isCurrentDuplicate'] = '1';
                                $updateArray['currentDuplicateDetails'][] = $dupEl;
                            }
                        }

                       
                    }
                }

                $updateArray['duplicateDitails']        = json_encode($updateArray['duplicateDitails']);
                $updateArray['currentDuplicateDetails'] = json_encode($updateArray['currentDuplicateDetails']);
                

                $updateArray['runTime'] = microtime(1) - $startTime;
                T3Db::api()->update("leads_quality", $updateArray, "id={$el['id']}");
            }
        }
    }
    
    /**
    * Обновить данные о фроде
    * 
    * @param int    $leadID
    * @param int    $lavel      0 - 128
    * @param mixed  $details
    */
    static public function updateAnalyticFroud($leadID, $lavel, $details){        
        $leadID = (int)$leadID;
        
        $lavel = (int)$lavel;
        if($lavel < 0)   $lavel = 0;
        if($lavel > 128) $lavel = 128;
        
        T3Db::api()->update("leads_quality", array(
            'isAnalyticFraud'       => $lavel,
            'analyticFraudDetails'  => serialize($details),    
        ), "id='{$leadID}'");      
    }
    
    /**
    * Обновить данные о хардкодах
    * 
    * @param int    $leadID
    * @param int    $lavel      0 - 128
    * @param mixed  $details
    */
    static public function updateAnalyticHardCodes($leadID, $lavel, $details){        
        $leadID = (int)$leadID;
        
        $lavel = (int)$lavel;
        if($lavel < 0)   $lavel = 0;
        if($lavel > 128) $lavel = 128;
        
        T3Db::api()->update("leads_quality", array(
            'isAnalyticHardCodes'       => $lavel,
            'AnalyticHardCodesDetails'  => serialize($details),    
        ), "id='{$leadID}'");      
    }

}

