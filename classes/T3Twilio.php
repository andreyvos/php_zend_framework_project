<?php

/*
class T3Twilio {

    protected $SID = 'ACac389bbf2b3bf238ff71fb4e344cbf9f';
    protected $TOKEN = 'bb6debff987fa8231c4e3b764844c87a';

    public $client;

    public function __construct(){
        $this->client = new Services_Twilio($this->SID, $this->TOKEN);
    }

    public function search_phone_numbers($country,$region=null,$type){

        $numbers = $this->client->account->available_phone_numbers->getList($country, $type, array('InRegion' => $region));

        $result = array();

        foreach ($numbers->available_phone_numbers as $number) {
            $num = $number->phone_number;
            if ($country == 'GB'){
                $num = str_replace('+44','',$num);
            }else{
                $num = str_replace('+1','',$num);
            }

            $result[] = array(
                'phone_number' => $number->phone_number,
                'number' => $num,
                'region' => $number->region,
                'postal_code' => $number->postal_code,
                'voice' => $number->capabilities->voice,
                'sms' => $number->capabilities->SMS,
            );
        }
        return $result;
    }

    public function calls($phone){

        $calls = $this->client->account->calls->getIterator(0, 50, array('To' => $phone));

        $result = array();

        foreach ($calls as $call) {
            $result[] = array(
                'sid'=>$call->sid,
                'from'=>$call->from,
                'status'=>$call->status,
                'start_time'=>date("Y-m-d H:i:s",strtotime($call->start_time)),
                'duration'=>$call->duration,
                'recording'=>$this->recording($call->sid)
            );
        }

        return $result;
    }

    public function recording($sid){
        $recordings = $this->client->account->recordings->getIterator(0, 50, array('CallSid' => $sid));
        foreach ($recordings as $recording) {
           return $recording->sid;
        }
    }

    public function sms($phone){
        $messages = $this->client->account->messages->getIterator(0, 50, array(
            'To' => $phone,
        ));

        $result = array();

        foreach ($messages as $message) {
            $result[] = array(
                'from' => $message->from,
                'date' => date("Y-m-d H:i:s",strtotime($message->date_sent)),
                'body' => $message->body,
            );
        }
        return $result;
    }

    public function phone_numbers(){

        $phoneNumbers = $this->client->account->incoming_phone_numbers->getIterator(0, 50, array(
        ));

        $result = array();

        foreach ($phoneNumbers as $phoneNumber) {
            $phone_format = $this->phone_format($phoneNumber->phone_number);
            $result[] = array(
                'phone_number' => $phoneNumber->phone_number,
                'num' => $phone_format['phone'],
                'country' => $phone_format['country'],
                'date_created' => date("Y-m-d H:i:s",strtotime($phoneNumber->date_created)),
                'voice' => $phoneNumber->capabilities->voice,
                'sms'=> $phoneNumber->capabilities->sms,
            );
        }
        return $result;
    }

    public function phone_format($phone){
        $country = "";

        if (substr($phone,1,1) == "1"){
            $country = 'US';
        }else if (substr($phone,1,2) == "44"){
            $country = 'GB';
        }

        if ($country == 'GB'){
            $phone = str_replace('+44','',$phone);
        }else{
            $phone = str_replace('+1','',$phone);
        }

        return array(
            'country' => $country,
            'phone' => $phone
        );
    }

    public function purchase_phone($phone,$buyer_name){
        $number = $this->client->account->incoming_phone_numbers->create(array(
            "FriendlyName" => $buyer_name,
            "PhoneNumber" => $phone,
            'SmsUrl' => "http://system.t3leads.com/system/twillio/sms.php",
            'VoiceUrl' => "http://system.t3leads.com/system/twillio/call.php",
            "VoiceMethod" => "GET",
            "SmsMethod" => "GET"
        ));
        return $number->sid;
    }

    public function get_phone($country,$region,$buyer_name="Buyer test",$type='Local'){
        $phone_list = $this->search_phone_numbers($country,$region,$type);
        if (count($phone_list)>0){
            foreach ($phone_list as $item){
                $isset = T3DB::api()->fetchOne("select id from seeding_phones where phone_iso=?",$item['phone_number']);
                if (!$isset){
                    $purchase_sid = @$this->purchase_phone($item['phone_number'],$buyer_name);
                    if (strlen($purchase_sid) > 0){
                        return $item['number'];
                    }else{
                        return false;
                    }
                }
            }
        }else{
            return false;
        }
    }

    public static function newcall($phone){
        $phone_id = T3db::apiReplicant()->fetchOne("select id from seeding_phones where phone_iso=?",$phone);
        if ($phone_id > 0){
            $array = array(
                'phone_id' => $phone_id,
                'date' => date("Y-m-d H:i:s"),
                'is_checked' => 0
            );
            T3DB::api()->insert('seeding_newcall',$array);
        }
    }

    public static function newsms($phone){
        $phone_id = T3db::apiReplicant()->fetchOne("select id from seeding_phones where phone_iso=?",$phone);
        if ($phone_id > 0){
            $array = array(
                'phone_id' => $phone_id,
                'date' => date("Y-m-d H:i:s"),
                'is_checked' => 0
            );
            T3DB::api()->insert('seeding_newsms',$array);
        }
    }

    public function getnewcalls(){
        $phones = T3DB::api()->fetchAll("select seeding_phones.phone_iso,seeding_phones.id from seeding_phones,seeding_newcall where seeding_phones.id=seeding_newcall.phone_id and seeding_newcall.is_checked=0 group by seeding_phones.id");
        foreach ($phones as $item){
            $calls = $this->calls($item['phone_iso']);
            foreach ($calls as $elem){
                $isset = T3DB::api()->fetchOne("select `id` from `seeding_calls` where `phone_id`='".$item['id']."' and `date`='".$elem['start_time']."' and `from`='".$elem['from']."'");
                if (!$isset){
                    $data = array(
                        'phone_id' => $item['id'],
                        'from' => $elem['from'],
                        'date' => $elem['start_time'],
                        'sid' => $elem['sid'],
                        'status' => $elem['status'],
                        'duration' => $elem['duration'],
                        'recording' => $elem['recording'],
                        'new' => 1
                    );
                    T3DB::api()->insert('seeding_calls',$data);
                    T3DB::api()->update('seeding_newcall',array("is_checked"=>1),"phone_id=".$item['id']);

                    $task_id = T3DB::api()->fetchOne("select seeding_task.id from seeding_task,seeding_phones where seeding_task.lead_id=seeding_phones.lead_id and seeding_phones.id=".$item['id']);
                    T3DB::api()->query("UPDATE seeding_task SET `call`=`call`+1 WHERE id=".$task_id);
                }
            }
        }
    }

    public function getnewsms(){
        $phones = T3DB::api()->fetchAll("select seeding_phones.phone_iso,seeding_phones.id from seeding_phones,seeding_newsms where seeding_phones.id=seeding_newsms.phone_id and seeding_newsms.is_checked=0 group by seeding_phones.id");
        foreach ($phones as $item){
            $sms = $this->sms($item['phone_iso']);
            foreach ($sms as $elem){
                $isset = T3DB::api()->fetchOne("select `id` from `seeding_sms` where `phone_id`='".$item['id']."' and `date`='".$elem['date']."' and `from`='".$elem['from']."'");
                if (!$isset){
                    $data = array(
                        'phone_id' => $item['id'],
                        'from' => $elem['from'],
                        'date' => $elem['date'],
                        'body' => $elem['body'],
                        'new' => 1
                    );
                    T3DB::api()->insert('seeding_sms',$data);
                    T3DB::api()->update('seeding_newsms',array("is_checked"=>1),"phone_id=".$item['id']);

                    $task_id = T3DB::api()->fetchOne("select seeding_task.id from seeding_task,seeding_phones where seeding_task.lead_id=seeding_phones.lead_id and seeding_phones.id=".$item['id']);
                    T3DB::api()->query("UPDATE seeding_task SET sms=sms+1 WHERE id=".$task_id);
                }
            }
        }
    }

}

*/