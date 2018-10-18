<?php

class T3CallDup {

    static public function addPhoneLeads($phone,$lead_id,$product_id,$status,$datetime){
        if (strlen($phone)>=10){
            $isset_phone = T3Db::api()->fetchOne("select id from phone where phone=? and product_id=?",array($phone,$product_id));
            if ($isset_phone){
                $phone_id = $isset_phone;
            }else{
                t3db::api()->insert('phone',array(
                    'phone'=>$phone,
                    'product_id'=>$product_id,
                    'data_id'=>0
                ));
                $phone_id = T3DB::api()->lastInsertId();    
            }
                
            $lead_status = 0;
            if ($status == 'sold'){
                $lead_status = 1;    
            }
            
            t3db::api()->insert('phone_leads',array(
                'lead_id'=>$lead_id,
                'phone_id'=>$phone_id,
                'status'=>$lead_status,
                'lead_datetime'=>$datetime
            ));
        }
    }
    
    static public function addPhoneCalls($phone,$lead_id,$product_id,$status,$datetime,$channel_id){
        if (strlen($phone)>=10){
            $isset_phone = T3Db::api()->fetchOne("select id from phone where phone=? and product_id=?",array($phone,$product_id));
            if ($isset_phone){
                $phone_id = $isset_phone;
            }else{
                t3db::api()->insert('phone',array(
                    'phone'=>$phone,
                    'product_id'=>$product_id,
                    'data_id'=>0
                ));
                $phone_id = T3DB::api()->lastInsertId();    
            }
                
            $lead_status = 0;
            if ($status == 'sold'){
                $lead_status = 1;    
            }
            
            t3db::api()->insert('phone_calls',array(
                'lead_id'=>$lead_id,
                'phone_id'=>$phone_id,
                'status'=>$lead_status,
                'lead_datetime'=>$datetime,
                'channel_id'=>$channel_id
            ));
        }
    }
    
    
}
