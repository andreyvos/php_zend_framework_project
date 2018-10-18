<?php

class T3Call {

    public $lead_id;
    public $product;
    public $product_int;
    public $channel;
    public $is_prefilerquestions;
    public $prefilerquestions=array();
    public $client_phone;
    public $dialed_phone;
    public $lead_segmentor;
    public $client;
    public $exist_lead_id;
    public $request=array();
    public $response=array();

    public function auth($request){
        $this->request = $request;
        $dialed_phone = $this->request['dialed_number'];
        $client_phone = $this->request['lead_callerid'];
        $product = $this->request['product'];


        if (substr($dialed_phone,0,1) == 1){
            $dialed_phone = substr($dialed_phone,1);
        }

        if (substr($client_phone,0,1) == 1){
            $client_phone = substr($client_phone,1);
        }

        $first_ping = ifset($this->request['first_ping']);

        $data = T3db::api()->fetchRow("select * from phone_call_numbers where product=? and phone=?",array($product,$dialed_phone));
        if (!$data){
            $this->channel = false;
        }else{
            $channel_id = $data['channel_id'];

            if ($data['product'] == 'call'){
                $this->is_prefilerquestions = 1;
            }else if ($data['product'] == 'callautotitle'){
                $this->is_prefilerquestions = 1;
            }else{
                $this->is_prefilerquestions = 0;
            }
            $this->client_phone = $client_phone;
            $this->dialed_phone = $dialed_phone;
            $this->exist_lead_id = ifset($this->request['lead_id']);
            $this->channel = T3Channels::getChannel($channel_id);
            $this->product = $data['product'];
            $this->product_int = T3Products::getID($data['product']);
            $this->first_ping = $first_ping;

            if ($this->first_ping == '1'){

                $this->findclient();
                if (!$this->client){
                    $result = ',,,,,,0';

                    T3DB::api()->insert('phone_call_log',array(
                        'date'=>date("Y-m-d H:i:s"),
                        'lead_id'=>0,
                        'request'=>serialize($this->request),
                        'response'=>$result
                    ));

                    echo $result;
                    exit();
                }
            }else{
                if (strlen(ifset($this->request['zip']))!=5){
                    $this->findclient();
                }else{
                    $this->client = false;
                }
            }
        }
    }

    public function findclient(){

        $client = t3db::apiReplicant()->fetchRow("SELECT leads_data_callautotitle.* FROM leads_data,leads_data_callautotitle WHERE leads_data.id=leads_data_callautotitle.id AND leads_data_callautotitle.phone=? AND UNIX_TIMESTAMP(leads_data.datetime)>? and leads_data_callautotitle.zip is not null order by leads_data_callautotitle.id desc limit 1",array($this->client_phone,(date("U")-86400*14)));
        if ($client){
            foreach ($client as $key=>$value){
                $this->client[$key] = $value;
            }
        }else{
            $this->client = false;
        }
    }

    public function findlastcall(){

        $n = 1;
        $last_buyers = T3DB::api()->fetchAll("SELECT * FROM phone_call WHERE product=".$this->product_int." AND phone='".$this->client_phone."' AND dialed_phone='".$this->dialed_phone."' AND buyer_phone<>'0' AND UNIX_TIMESTAMP(`start_datetime`)>".(date("U")-86400*7)." order by `start_datetime` desc");
        if (count($last_buyers)>0){
            foreach ($last_buyers as $item){
                $this->response['lastbuyer'] = '1'.$item['buyer_phone'];
                $n++;
            }
            $this->response['call_count'] = $n;
        }else{
            $this->response['call_count'] = 1;
            $this->response['lastbuyer'] = null;

        }
    }

    public function save(){

        $this->findlastcall();
        $this->response['buyer_count'] = 0;
        $ivr_type = '';
        $ivr_leadid = '';
        if ($this->channel){
            $a = array();

            $a["id"] = $this->channel->getID;
            $a["password"] = $this->channel->password;
            $a["client_ip_address"] = "1.1.1.1";
            $a["minimum_price"] = "0";
            $a["phone"] = $this->client_phone;

            if ($this->client === false){

                $this->get_prefilter_questions();
                $ivr_type = 'ivr';
            }else{

                $ivr_type = 'existing';
                $ivr_leadid = $this->client['id'];
            }


            if ($this->client){
                foreach ($this->client as $key=>$value){
                    if ($key!='id'){
                        $a[$key] = $value;
                    }
                }
            }

            $postValuesString = "";

            foreach($a as $var => $val){
                if(strlen($postValuesString))$postValuesString.= "&";
                $postValuesString.= $var . "=" . urlencode($val);
            }

            $server_url = "https://system.t3leads.com/system/lead_channel/server_post.php";
            $Response = file_get_contents($server_url.'?'.$postValuesString);
            if($Response){

                $regexp = "/\<ID\>(.+)\<\/ID\>/ism";
                preg_match($regexp, $Response, $matches);
                $lead = trim($matches[1]);
                if ($lead){

                    T3DB::api()->insert('phone_ivrlog',array(
                        'lead_id'=>$lead,
                        'type'=>$ivr_type,
                        'stored_id'=>$ivr_leadid
                    ));


                    $this->lead_id = $lead;
                    $this->response['lead_id'] = $lead;
                    $lead_obj = new T3Lead();
                    $lead_obj->fromDatabase($lead);
                    $lead_obj->getBodyFromDatabase();
                    $pingTree = new T3PingTree($lead_obj);

                    $runAll = $lead_obj->getPostingsSended();      // каналы на которые этот лид уже постался
                    foreach($pingTree->order as $k => $buyerChannelID){
                        if(in_array($buyerChannelID, $runAll)){
                            unset($pingTree->order[$k]);
                        }
                    }
                    
                    if(count($pingTree->order)){
                        $activeChannels = T3Db::api()->fetchCol(   // все активные на данный момент каналы
                            "select id from buyers_channels where id in (" . implode(",", $pingTree->order) . ") and `status`='active' and filter_datetime=1"
                        );
                        foreach($pingTree->order as $k => $buyerChannelID){

                            // Removing inactive channels
                            if(!in_array($buyerChannelID, $activeChannels)){
                                unset($pingTree->order[$k]);
                            }

                            $buyerChannel = T3BuyerChannels::getChannel($buyerChannelID);

                            //Removing channels based on DNPL
                            $phone_in_dnpl = T3Db::apiReplicant()->fetchOne("SELECT id FROM dnpl_calls WHERE buyer=? AND (cellphone=? OR homephone=?)",array($buyerChannel->buyer_id,$this->client_phone,$this->client_phone));

                            if($phone_in_dnpl && isset($pingTree->order[$k])){

                                unset($pingTree->order[$k]);

                            }
                        }
                    }

                    foreach($pingTree->order as $k => $buyerChannelID){
                        $filter = T3BuyerFilters::getInstance()->getFilter($buyerChannelID);

                        $filterResult = $filter->acceptsLead($lead_obj);
                        if(!$filterResult->isError() || ($filterResult->messages[count($filterResult->messages)-1]->subject == 'Date' && $filterResult->isError())){

                        }else{
                            unset($pingTree->order[$k]);
                        }
                    }

                    if (count($pingTree->order)>0){
                        $i=0;
                        foreach ($pingTree->order as $channel_id){
                            $config = T3DB::api()->fetchOne("select settings from buyers_channels where id=$channel_id");
                            if ($config){
                                $config_array = unserialize($config);
                                if (isset($config_array['Data']['collect_numbers']) && strlen(trim($config_array['Data']['collect_numbers']))>=10){

                                    $i++;
                                    $this->response['buyer_phone'.$i] = '1'.trim($config_array['Data']['collect_numbers']);

                                }
                            }
                        }
                        $this->response['buyer_count'] = $i;
                    }

                }
            }
        }
        $this->savelog();
    }

    public function get_prefilter_questions(){
        $client = array();

        if ($this->product == 'call'){
            if (isset($this->request['cash_advance-question1_18']) && isset($this->request['cash_advance-question2_chk']) && isset($this->request['cash_advance-question3_dir']) && isset($this->request['cash_advance-question4_inc'])){
                $param1 = ifset($this->request['cash_advance-question1_18']);
                $param2 = ifset($this->request['cash_advance-question2_chk']);
                $param3 = ifset($this->request['cash_advance-question3_dir']);
                $param4 = ifset($this->request['cash_advance-question4_inc']);

                $client = array(
                    'phone'=>$this->client_phone,
                    'direct_deposit'=>'0',
                    'years18old'=>'0',
                    'bank_account_type'=>'SAVING',
                    'income_type'=>'BENEFITS',
                );

                if ($param1 == 1){
                    $client['years18old'] = 1;
                }
                if ($param2 == 1){
                    $client['bank_account_type'] = 'CHECKING';
                }
                if ($param3 == 1){
                    $client['direct_deposit'] = 1;
                }
                if ($param4 == 1){
                    $client['income_type'] = 'EMPLOYMENT';
                }
            }
        }

        if ($this->product == 'callautotitle'){

            if (isset($this->request['own_title']) && isset($this->request['debt_bankrupcy']) && isset($this->request['zip']) && isset($this->request['year']) && isset($this->request['mileage'])){
                $param1 = ifset($this->request['own_title']);
                $param2 = ifset($this->request['debt_bankrupcy']);
                $param3 = ifset($this->request['zip']);
                $param4 = ifset($this->request['year']);
                $param5 = ifset($this->request['mileage']);

                $client = array(
                    'phone'=>$this->client_phone,
                    'own_vehicle_title'=>'0',
                    'zip'=>$param3,
                    'bankruptcy'=>'0',
                    'vehicle_year'=>$param4,
                    'vehicle_mileage'=>$param5,
                );

                if ($param1 == 1){
                    $client['own_vehicle_title'] = 1;
                }
                if ($param2 == 1){
                    $client['debt_bankrupcy'] = 1;
                }

            }

            if (isset($this->exist_lead_id) && $this->exist_lead_id>0){

                $exist_payday = T3DB::api()->fetchRow("select direct_deposit,bank_account_type,monthly_income,income_type,years18old from leads_data_call where id=?",array($this->exist_lead_id));
                if ($exist_payday){
                    foreach ($exist_payday as $key=>$val){
                        $client[$key]=$val;
                    }
                }
            }
        }
        foreach ($client as $key=>$val){
            $this->client[$key]=$val;
        }
    }

    public function savelog(){
        T3DB::api()->insert('phone_call_log',array(
            'date'=>date("Y-m-d H:i:s"),
            'lead_id'=>$this->lead_id,
            'request'=>serialize($this->request),
            'response'=>serialize($this->response)
        ));
    }

}