<?php

class T3Synh_PostingLeads {
    protected static $error = null;
    
    static public function getError(){
        return self::$error;    
    }
    
    static protected function setError($message){
        self::$error = $message;    
    }
    
    static public function updateLeads($count = 100){
        $count = (int)$count;
        if($count < 0) $count = 0;
        
        
        T3Db::v1()->insert('session', array());
        $session = T3Db::v1()->lastInsertId();
        
        
        
        T3Db::v1()->query("update money_synh set `status`='brone', `session`='{$session}', `broneStart` = NOW() where `status`='nosend' limit {$count}");

        $array = T3Db::v1()->fetchAll("select id,createDate,leadID,v2ChannelID,`amount` from money_synh where `session`='{$session}'");
        
        if(count($array)){
            foreach($array as $el){
                $save = false;
                
                $el['email']        =   (string)T3Db::v1()->fetchOne("select val from form_data where id='{$el['leadID']}' and var='EMAIL'");
                $el['ssn']          =   (string)T3Db::v1()->fetchOne("select val from form_data where id='{$el['leadID']}' and var='SSN'");
                $el['home_phone']   =   (string)T3Db::v1()->fetchOne("select val from form_data where id='{$el['leadID']}' and var='PRI_PHONE'");  
                
                T3Db::api()->beginTransaction();
                try { 
                    $channel = new T3BuyerChannel();
                    $channel->fromDatabase((int)$el['v2ChannelID']);
                    
                    if($channel->id){
                        $amount = round((float)$el['amount'],2);
                        
                        $buyer = new T3BuyerCompany();
                        $buyer->fromDatabase($channel->buyer_id);
                        $buyer->updateBalance(-$amount);
                    
                        $insertArray = array(
                            'lead_id'               => $el['leadID'],
                            'channel_id'            => $channel->id,
                            'buyer_id'              => $channel->buyer_id,
                            'action_datetime'       => $el['createDate'],
                            'action_sum'            => $amount,
                            'lead_product'          => $channel->product,
                            'is_v1_lead'            => 1,
                            'lead_email'            => $el['email'],
                            'lead_home_phone'       => $el['home_phone'],
                            'syncId'                => $el['id'],
                            'invoice_id'            => 0,
                        );
                        
                        if($el['amount'] > 0){
                            $insertTable = 'buyers_leads_sellings';    
                        }
                        else {
                            $insertTable = 'buyers_leads_movements'; 
                        } 

                        
                        if(T3System::getConnect()->insert($insertTable, $insertArray)){
                            $save = true;        
                        }
                    }
                    
                    T3Db::api()->commit();
                     
                } 
                catch (Exception $e) {
                    T3Db::api()->rollBack(); 
                    $error = $e->getMessage();
                    $save = false;
                }
                
                try{
                    if($save){
                        T3Db::v1()->update('money_synh', array('status' => 'ok'), "id={$el['id']}");
                    }
                    else {
                        T3Db::v1()->update('money_synh', array(
                            'status'   => 'error',
                            'request'  => varExportSafe($el),
                            'response' => varExportSafe($error),
                        ), "id={$el['id']}");     
                    }
                }
                catch(Exception $e){
                    
                }
            }
            
        }
    }
    
    
    static public function freeOldBrone(){
        $oldBrone = T3Db::v1()->fetchAll("select id,`amount` from money_synh where `status`='brone' and UNIX_TIMESTAMP(broneStart) < UNIX_TIMESTAMP(NOW()) - 7200");
        
        if(count($oldBrone)){
            foreach($oldBrone as $el){
                if($el['amount'] > 0){  
                    $insertTable = 'buyers_leads_sellings';    
                }
                else {
                    $insertTable = 'buyers_leads_movements';    
                }
                
                if(T3Db::api()->fetchOne("select count(*) from {$insertTable} where syncId=?", $el['id'])){
                    // Есть такая запись 
                    T3Db::v1()->update('money_synh', array('status' => 'finish'), "id={$el['id']}"); 
                    echo " Update: Finish ";  
                }
                else {
                    // Нет такой записи
                    T3Db::v1()->update('money_synh', array('status' => 'nosend'), "id={$el['id']}");   
                    " Update: Nosend "; 
                }
            } 
            
            echo count($oldBrone) . " Values";  
        }
        else {
            echo "Not Values";
        }        
    }
       
}