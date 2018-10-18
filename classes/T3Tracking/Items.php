<?php

class T3Tracking_Items {
    /**
    * @return T3Tracking_Item
    */
    static public function createTest($webmasterID){
        $item = new T3Tracking_Item();
        
        $item->id = 0;
        $item->type = 'test';
        $item->create_date = date("Y-m-d H:i:s");
        $item->webmaster_id = $webmasterID; 
        $item->channel_id = '1';
        $item->lead_id = '1.' . $webmasterID;
        $item->product = 'payday'; 
        $item->comment = 'Test'; 
        $item->subaccount = 'test';
        $item->price = '0';
        
        return $item; 
    }
    
    static public function addLead(T3Lead $lead){
        $setting = T3Tracking_Settings::getSetting($lead->affid);
        if($setting->isRun('newLead')){
            $item = new T3Tracking_Item();
            $item->type = 'newLead';
            $item->create_date = date("Y-m-d H:i:s");   
            $item->webmaster_id = $lead->affid; 
            $item->channel_id = $lead->channel_id;
            $item->lead_id = $lead->getId;
            $item->product = $lead->product;
            $item->subaccount = $lead->subacc_str;
            $item->price = '0';
            $item->post_start_date = date("Y-m-d H:i:s"); 
            $item->insertIntoDatabase();
        }
    }
    
    static public function changeLeadPrice(T3Lead $lead, $price){
        $setting = T3Tracking_Settings::getSetting($lead->affid);
        if($setting->isRun('leadAddPrice')){
            $item = new T3Tracking_Item();
            $item->type = 'leadAddPrice';
            $item->create_date = date("Y-m-d H:i:s");   
            $item->webmaster_id = $lead->affid; 
            $item->channel_id = $lead->channel_id;
            $item->lead_id = $lead->getId;
            $item->product = $lead->product;
            $item->subaccount = $lead->subacc_str;
            $item->price = $price;
            $item->post_start_date = date("Y-m-d H:i:s"); 
            $item->insertIntoDatabase();
        }
    }
    
    static public function addReturn(T3Lead_Return $return){
        $setting = T3Tracking_Settings::getSetting($return->affid);
        if($setting->isRun('leadReturn') && $return->wm != 0){
            $item = new T3Tracking_Item();
            $item->type = 'leadReturn';
            $item->create_date = date("Y-m-d H:i:s");   
            $item->webmaster_id = $return->affid; 
            $item->channel_id = $return->channel_id;
            $item->lead_id = T3Cache_LeadsID::get($return->lead_id, false);
            $item->product = $return->product;
            $item->subaccount = T3Leads::getSubaccountStr($return->subacc);
            $item->price = $return->wm;
            $item->post_start_date = date("Y-m-d H:i:s"); 
            $item->comment = substr($return->comment, 0, 255);
            $item->insertIntoDatabase();      
        }
    }
    
    static public function addBonus(T3Bonus $bonus){
        $setting = T3Tracking_Settings::getSetting($bonus->webmaster_id);
        if($setting->isRun('newBonus')){
            $item = new T3Tracking_Item();
            $item->type = 'newBonus';
            $item->create_date = date("Y-m-d H:i:s");   
            $item->webmaster_id = $bonus->webmaster_id; 
            $item->channel_id = $bonus->lead_channel_id;
            $item->lead_id =  T3Cache_LeadsID::get($bonus->lead_id, false);
            $item->product = $bonus->lead_product;
            $item->subaccount = '';
            $item->price = $bonus->action_sum;
            $item->post_start_date = date("Y-m-d H:i:s"); 
            $item->comment = substr($bonus->comment, 0, 255);
            $item->insertIntoDatabase();
        }
    } 
    
    static public function postAll($count = 100){
        $count = (int)$count;
        if($count < 0) $count = 0;
        
        T3Db::api()->insert('session', array());
        $session = T3Db::api()->lastInsertId(); 
        
        T3Db::api()->query("update tracking_items set `post_status`='brone', `session`='{$session}', `broneStart` = NOW() where post_start_date<NOW() and `post_status`='free' limit {$count}");   
        
        $array = T3Db::api()->fetchAll("select * from tracking_items where `session`='{$session}'");
        
        if(count($array)){
            $item = new T3Tracking_Item();
            
            foreach($array as $el){
                $item->setParams($el);
                $result = $item->post();
                
                /* 
                    $result = array(
                        'success'           => false,
                        'reason'            => '',
                        'request_header'    => '',
                        'response_header'   => '',
                        'response'          => '',
                    );
                */
                
                if($result['success'] === true){
                    // Удачно
                    T3Db::api()->update("tracking_items", array(
                        'post_status' => 'ok',
                    ), "id={$item->id}");   
                }
                else if($result['success'] === false) {
                    // Не удачно 
                    if($item->post_errors_count >= 5){
                        // Больше попыток не осталось, УВЫ...
                        T3Db::api()->update("tracking_items", array(
                            'post_status' => 'error',
                        ), "id={$item->id}");
                    }
                    else {
                        // Записаться на прием через 2 часа :)
                        T3Db::api()->update("tracking_items", array(
                            'post_status' => 'free',
                            'post_errors_count' => $item->post_errors_count + 1,
                            'post_start_date' => date("Y-m-d H:i:s", mktime()+7200),
                        ), "id={$item->id}");
                    }  
                }
                else {
                    T3Db::api()->delete("tracking_items", "id={$item->id}");    
                }
                
                
            }   
        } 
    }  
}