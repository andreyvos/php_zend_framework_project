<?php

class T3CallReport {

    public $call_fwd_uid;
    public $lead_id;
    public $lead;
    public $product_int;
    public $channel_id;
    public $client_phone;
    public $dialed_phone;
    public $buyer_phone;
    public $duration;
    public $start_connected_time;
    public $end_date_time;
    public $dialstatus;
    public $request=array();
    public $response=array();

    public function auth($request){
        $this->request = $request;

        if (substr($this->request['dialed_number'],0,1) == 1){
            $this->request['dialed_number'] = substr($this->request['dialed_number'],1);
        }
        if (substr($this->request['caller_id'],0,1) == 1){
            $this->request['caller_id'] = substr($this->request['caller_id'],1);
        }
        if (substr($this->request['forwarded_number'],0,1) == 1){
            $this->request['forwarded_number'] = substr($this->request['forwarded_number'],1);
        }

        $this->dialed_phone = $this->request['dialed_number'];
        $this->client_phone = $this->request['caller_id'];
        $this->buyer_phone = $this->request['forwarded_number'];
        $this->lead_id = $this->request['lead_id'];
        $this->call_fwd_uid = $this->request['call_fwd_uid'];

        $this->channel_id = T3DB::api()->fetchOne("select id from buyers_channels where product rlike 'call' and settings rlike ? order by id limit 1",array($this->buyer_phone));

        $start_datetime = ifset($this->request['start_connected_time'],'0000-00-00 00:00:00');
        $end_datetime = ifset($this->request['end_date_time'],'0000-00-00 00:00:00');
        $total_time = 0;
        if (strtotime($end_datetime)>0 && strtotime($end_datetime)>strtotime($start_datetime)){
            $total_time = strtotime($end_datetime)-strtotime($start_datetime);
        }
        $this->duration = $total_time;
        $this->start_connected_time = $start_datetime;
        $this->end_date_time = $end_datetime;
        $this->dialstatus = $this->request['dialstatus'];

    }

    public function addnumreport(){
        $isset = T3DB::api()->fetchOne("select id from phone_dailysent where affid=? and `date`=?",array($this->lead['affid'],date("Y-m-d")));
        if ($isset){
            T3DB::api()->query("update phone_dailysent set `num`=`num`+1 where id=?",array($isset));
        }else{
            $data = array(
                'date'=>date("Y-m-d"),
                'affid'=>$this->lead['affid'],
                'num'=>1
            );
            T3DB::api()->insert('phone_dailysent',$data);
        }
    }

    public function addreport(){
        $isset_call = T3DB::api()->fetchCol("select * from phone_callduration where lead_id=? and channel_id=?",array($this->lead_id,$this->channel_id));
        if (!$isset_call){
            T3DB::api()->insert('phone_callduration',array(
                'date'=>$this->start_connected_time,
                'duration'=>$this->duration,
                'lead_id'=>$this->lead_id,
                'channel_id'=>$this->channel_id
            ));

            $lead_obj = new T3Lead();
            $lead_obj->fromDatabase($this->lead_id);
            $lead_obj->getBodyFromDatabase();

            $postResult = $lead_obj->postToCallBuyer($this->channel_id, false);

            $this->lead = T3DB::api()->fetchRow("select * from leads_data where id=?",array($this->lead_id));
            $this->product_int = T3Products::getID($this->lead['product']);

            $this->addnumreport();

            $data = array(
                'audio_id'=>$this->call_fwd_uid,
                'phone'=>$this->client_phone,
                'buyer_phone'=>$this->buyer_phone,
                'start_datetime'=>$this->start_connected_time,
                'end_datetime'=>$this->end_date_time,
                'total_time'=>$this->duration,
                'affid'=>$this->lead['affid'],
                'subacc'=>$this->lead['subacc_str'],
                'status'=>$this->dialstatus,
                'dialed_phone'=>$this->dialed_phone,
                'lead_id'=>$this->lead_id,
                'webmaster_channel_id'=>$this->lead['channel_id'],
                'server'=>2
            );
            $data['channel_id'] = $this->channel_id;
            $data['product'] = $this->product_int ;
            $data['buyer_id'] = (int)T3DB::api()->fetchOne("select buyer_id from buyers_channels where id=?",array($this->channel_id));
            $data['hash'] = md5($this->call_fwd_uid.$this->lead_id.$this->channel_id.rand(1000000,999999999));



            if ($postResult->isSold()){
                $data['lead_status'] = 'sold';
                $data['wm'] = $this->lead['wm'];
                $data['ref'] = $this->lead['ref'];
                $data['agn'] = $this->lead['agn'];
                $data['t3'] = $this->lead['ttl']-$this->lead['wm']-$this->lead['ref']-$this->lead['agn'];
                $data['ttl'] = $this->lead['ttl'];
            }else{
                $data['lead_status'] = 'reject';
            }


            $data['quality_leads'] = T3Db::api()->fetchOne("SELECT COUNT(phone.`id`) FROM phone,phone_leads WHERE phone.`id`=phone_leads.`phone_id` AND phone.`phone`=".$this->client_phone);
            $data['quality_calls'] = T3Db::api()->fetchOne("SELECT COUNT(phone.`id`) FROM phone,phone_calls WHERE phone.`id`=phone_calls.`phone_id` AND phone.`phone`=".$this->client_phone);
            $data['quality_channel_id'] = T3Db::api()->fetchOne("SELECT COUNT(phone.`id`) FROM phone,phone_calls WHERE phone.`id`=phone_calls.`phone_id` AND phone.`phone`=? AND phone_calls.`channel_id`=?",array($this->client_phone,$this->channel_id));
            $relatedlead_id = (int)T3Db::api()->fetchOne("SELECT phone_leads.`lead_id` FROM phone,phone_leads WHERE phone.`id`=phone_leads.`phone_id` AND phone.`phone`=? ORDER BY phone_leads.`lead_id` DESC LIMIT 1",array($this->client_phone));
            if ($relatedlead_id){
                $data['related_lead_id'] = $relatedlead_id;
            }

            T3DB::api()->insert('phone_call',$data);



            $related_product = T3DB::api()->fetchAll("SELECT product_id FROM phone_relatedproduct WHERE callproduct_id=?",array($this->product_int));
            foreach ($related_product as $item){
                T3CallDup::addPhoneCalls($this->client_phone,$this->lead_id,$item['product_id'],$data['lead_status'],$this->lead['datetime'],$this->channel_id);
            }
        }

    }

    public function savelog(){
        T3DB::api()->insert('phone_call_log',array(
            'date'=>date("Y-m-d H:i:s"),
            'lead_id'=>$this->lead_id,
            'request'=>serialize($this->request),
            'response'=>"recieved"
        ));
    }
}