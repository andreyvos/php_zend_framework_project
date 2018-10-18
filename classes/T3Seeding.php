<?php

class T3Seeding {

    public function free_email(){
        return T3DB::api()->fetchRow("select * from seeding_emails where lead_id is null limit 1");
    }

    public function search_lead($state,$price,$product,$channel_id){
        $min_date = date("Y-m-d H:i:s",(int)date("U")-86400*30);
        $lead = T3Db::apiReplicant()->fetchOne("select leads_data.id from leads_data,leads_quality where leads_data.id=leads_quality.id and leads_data.datetime>'$min_date' and leads_quality.duplicateCount=0 and leads_data.data_state='$state' and leads_data.product='$product' and leads_data.ttl>$price limit 1");
        if ($lead){
            return $lead;
        }else{
            $price = $price-50;
            $leads = T3Db::apiReplicant()->fetchAll("select leads_data.id,leads_data.data_email from leads_data,leads_quality where leads_data.id=leads_quality.id and leads_data.datetime>$min_date and leads_quality.duplicateCount=0 and leads_data.data_state='$state' and leads_data.product='$product' and leads_data.ttl>$price limit 20");
            foreach ($leads as $elem){
                $isset = T3DB::apiReplicant()->fetchOne("select id from buyer_channels_dup_email_post where email=? and posting=?",$elem['data_email'],$channel_id);
                if (!$isset){
                    return $elem['id'];
                }
            }
        }
        return false;
    }

    public function get_pay_dates($pay_frequency,$product){

        $result = array(
            'pay_date1'=>null,
            'pay_date2'=>null,
        );

        if ($product == 'payday'){

            $mktime = mktime(0, 0, 0, date("m"), date("d")+2, date("Y"));
            $dateW = date("w",$mktime);
            if($dateW == "6" || $dateW == "0")$mktime += 86400*3;

            $result['pay_date1'] = date("Y-m-d", $mktime);

            if ($pay_frequency == 'WEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*7);
            }else if ($pay_frequency == 'BIWEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*14);
            }else if ($pay_frequency == 'TWICEMONTHLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*14);
            }else if ($pay_frequency == 'MONTHLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*28);
            }

        }else if ($product == 'ukpayday'){

            $mktime = mktime(0, 0, 0, date("m"), date("d")+2, date("Y"));
            $dateW = date("w",$mktime);
            if($dateW == "6" || $dateW == "0")$mktime += 86400*3;

            $result['pay_date1'] = date("Y-m-d", $mktime);

            if ($pay_frequency == 'FOUR_WEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*28);
            }else if ($pay_frequency == 'WEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*7);
            }else if ($pay_frequency == 'BIWEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*14);
            }else if ($pay_frequency == 'MONTHLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*28);
            }else if ($pay_frequency == 'SPECIFIC_DATE'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*28);
            }else if ($pay_frequency == 'LAST_WORKING_DAY_OF_MONTH'){

                $n = (int)date("t");
                for ($i=$n;$i>0;$i--){
                    $temp_date = mktime(0, 0, 0, date("m"), $i, date("Y"));
                    if (date("w",$temp_date)>0 && date("w",$temp_date)<6){
                        if ($i>(int)date("d")){
                            $result['pay_date1'] = date("Y-m-d", $temp_date);
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $m = (int)date("t",$temp_date);
                            for ($j=$m;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)>0 && date("w",$temp_date)<6){
                                    $result['pay_date2'] = date("Y-m-d", $temp_date);
                                    $j = 0;
                                }
                            }
                        }else{
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $n = (int)date("t",$temp_date);

                            for ($j=$n;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)>0 && date("w",$temp_date)<6){
                                    $result['pay_date1'] = date("Y-m-d", $temp_date);
                                    $j = 0;

                                    $temp_date = mktime(0, 0, 0, date("m")+2, 1, date("Y"));
                                    $m = (int)date("t",$temp_date);
                                    for ($k=$m;$k>0;$k--){
                                        $temp_date = mktime(0, 0, 0, date("m")+2, $k, date("Y"));
                                        if (date("w",$temp_date)>0 && date("w",$temp_date)<6){
                                            $result['pay_date2'] = date("Y-m-d", $temp_date);
                                            $k = 0;
                                        }
                                    }

                                }
                            }
                        }
                        $i = 0;
                    }
                }


            }else if ($pay_frequency == 'LAST_MONDAY_OF_MONTH'){

                $n = (int)date("t");
                for ($i=$n;$i>0;$i--){
                    $temp_date = mktime(0, 0, 0, date("m"), $i, date("Y"));
                    if (date("w",$temp_date)==1){
                        if ($i>(int)date("d")){
                            $result['pay_date1'] = date("Y-m-d", $temp_date);
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $m = (int)date("t",$temp_date);
                            for ($j=$m;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==1){
                                    $result['pay_date2'] = date("Y-m-d", $temp_date);
                                    $j = 0;
                                }
                            }
                        }else{
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $n = (int)date("t",$temp_date);

                            for ($j=$n;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==1){
                                    $result['pay_date1'] = date("Y-m-d", $temp_date);
                                    $j = 0;

                                    $temp_date = mktime(0, 0, 0, date("m")+2, 1, date("Y"));
                                    $m = (int)date("t",$temp_date);
                                    for ($k=$m;$k>0;$k--){
                                        $temp_date = mktime(0, 0, 0, date("m")+2, $k, date("Y"));
                                        if (date("w",$temp_date)==1){
                                            $result['pay_date2'] = date("Y-m-d", $temp_date);
                                            $k = 0;
                                        }
                                    }

                                }
                            }
                        }
                        $i = 0;
                    }
                }

            }else if ($pay_frequency == 'LAST_TUESDAY_OF_MONTH'){

                $n = (int)date("t");
                for ($i=$n;$i>0;$i--){
                    $temp_date = mktime(0, 0, 0, date("m"), $i, date("Y"));
                    if (date("w",$temp_date)==2){
                        if ($i>(int)date("d")){
                            $result['pay_date1'] = date("Y-m-d", $temp_date);
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $m = (int)date("t",$temp_date);
                            for ($j=$m;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==2){
                                    $result['pay_date2'] = date("Y-m-d", $temp_date);
                                    $j = 0;
                                }
                            }
                        }else{
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $n = (int)date("t",$temp_date);

                            for ($j=$n;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==2){
                                    $result['pay_date1'] = date("Y-m-d", $temp_date);
                                    $j = 0;

                                    $temp_date = mktime(0, 0, 0, date("m")+2, 1, date("Y"));
                                    $m = (int)date("t",$temp_date);
                                    for ($k=$m;$k>0;$k--){
                                        $temp_date = mktime(0, 0, 0, date("m")+2, $k, date("Y"));
                                        if (date("w",$temp_date)==2){
                                            $result['pay_date2'] = date("Y-m-d", $temp_date);
                                            $k = 0;
                                        }
                                    }

                                }
                            }
                        }
                        $i = 0;
                    }
                }

            }else if ($pay_frequency == 'LAST_WEDNESDAY_OF_MONTH'){

                $n = (int)date("t");
                for ($i=$n;$i>0;$i--){
                    $temp_date = mktime(0, 0, 0, date("m"), $i, date("Y"));
                    if (date("w",$temp_date)==3){
                        if ($i>(int)date("d")){
                            $result['pay_date1'] = date("Y-m-d", $temp_date);
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $m = (int)date("t",$temp_date);
                            for ($j=$m;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==3){
                                    $result['pay_date2'] = date("Y-m-d", $temp_date);
                                    $j = 0;
                                }
                            }
                        }else{
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $n = (int)date("t",$temp_date);

                            for ($j=$n;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==3){
                                    $result['pay_date1'] = date("Y-m-d", $temp_date);
                                    $j = 0;

                                    $temp_date = mktime(0, 0, 0, date("m")+2, 1, date("Y"));
                                    $m = (int)date("t",$temp_date);
                                    for ($k=$m;$k>0;$k--){
                                        $temp_date = mktime(0, 0, 0, date("m")+2, $k, date("Y"));
                                        if (date("w",$temp_date)==3){
                                            $result['pay_date2'] = date("Y-m-d", $temp_date);
                                            $k = 0;
                                        }
                                    }

                                }
                            }
                        }
                        $i = 0;
                    }
                }

            }else if ($pay_frequency == 'LAST_THURSDAY_OF_MONTH'){

                $n = (int)date("t");
                for ($i=$n;$i>0;$i--){
                    $temp_date = mktime(0, 0, 0, date("m"), $i, date("Y"));
                    if (date("w",$temp_date)==4){
                        if ($i>(int)date("d")){
                            $result['pay_date1'] = date("Y-m-d", $temp_date);
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $m = (int)date("t",$temp_date);
                            for ($j=$m;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==4){
                                    $result['pay_date2'] = date("Y-m-d", $temp_date);
                                    $j = 0;
                                }
                            }
                        }else{
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $n = (int)date("t",$temp_date);

                            for ($j=$n;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==4){
                                    $result['pay_date1'] = date("Y-m-d", $temp_date);
                                    $j = 0;

                                    $temp_date = mktime(0, 0, 0, date("m")+2, 1, date("Y"));
                                    $m = (int)date("t",$temp_date);
                                    for ($k=$m;$k>0;$k--){
                                        $temp_date = mktime(0, 0, 0, date("m")+2, $k, date("Y"));
                                        if (date("w",$temp_date)==4){
                                            $result['pay_date2'] = date("Y-m-d", $temp_date);
                                            $k = 0;
                                        }
                                    }

                                }
                            }
                        }
                        $i = 0;
                    }
                }

            }else if ($pay_frequency == 'LAST_FRIDAY_OF_MONTH'){

                $n = (int)date("t");
                for ($i=$n;$i>0;$i--){
                    $temp_date = mktime(0, 0, 0, date("m"), $i, date("Y"));
                    if (date("w",$temp_date)==5){
                        if ($i>(int)date("d")){
                            $result['pay_date1'] = date("Y-m-d", $temp_date);
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $m = (int)date("t",$temp_date);
                            for ($j=$m;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==5){
                                    $result['pay_date2'] = date("Y-m-d", $temp_date);
                                    $j = 0;
                                }
                            }
                        }else{
                            $temp_date = mktime(0, 0, 0, date("m")+1, 1, date("Y"));
                            $n = (int)date("t",$temp_date);

                            for ($j=$n;$j>0;$j--){
                                $temp_date = mktime(0, 0, 0, date("m")+1, $j, date("Y"));
                                if (date("w",$temp_date)==5){
                                    $result['pay_date1'] = date("Y-m-d", $temp_date);
                                    $j = 0;

                                    $temp_date = mktime(0, 0, 0, date("m")+2, 1, date("Y"));
                                    $m = (int)date("t",$temp_date);
                                    for ($k=$m;$k>0;$k--){
                                        $temp_date = mktime(0, 0, 0, date("m")+2, $k, date("Y"));
                                        if (date("w",$temp_date)==5){
                                            $result['pay_date2'] = date("Y-m-d", $temp_date);
                                            $k = 0;
                                        }
                                    }

                                }
                            }
                        }
                        $i = 0;
                    }
                }

            }

        }else if ($product == 'capayday'){

            $mktime = mktime(0, 0, 0, date("m"), date("d")+2, date("Y"));
            $dateW = date("w",$mktime);
            if($dateW == "6" || $dateW == "0")$mktime += 86400*3;

            $result['pay_date1'] = date("Y-m-d", $mktime);

            if ($pay_frequency == 'WEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*7);
            }else if ($pay_frequency == 'BIWEEKLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*14);
            }else if ($pay_frequency == 'TWICEMONTHLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*14);
            }else if ($pay_frequency == 'MONTHLY'){
                $result['pay_date2'] = date("Y-m-d", $mktime + 86400*28);
            }

        }

        return $result;

    }

    public function create_lead($channel_id){

        $product = T3DB::api()->fetchOne("select `product` from buyers_channels where id=$channel_id");

        $client_fields = array(
            'payday'=>array('first_name','last_name','ssn','birth_date','address','city','state','zip','drivers_license_number','drivers_license_state','bank_aba','bank_account_number'),
            'ukpayday'=>array('first_name','last_name','birth_date','bank_account_number','bank_sort_code','county','town','postcode','streetname','streetnumber'),
            'capayday'=>array('first_name','last_name','birth_date','address','city','province','postcode','bank_institution_number','bank_branch_number','bank_account_number')
        );

        $last_sold_lead = T3DB::apiReplicant()->fetchRow("select leads_data.id as 'id',leads_data.data_state as 'state',buyers_statistics_lite.earnings as 'price' from leads_data,buyers_statistics_lite where buyers_statistics_lite.lead_id=leads_data.id and buyers_statistics_lite.buyer_channel_id=$channel_id and buyers_statistics_lite.post_result_status='Sold' order by buyers_statistics_lite.id desc limit 1");
        if ($last_sold_lead){
            $good_customer = $this->search_lead($last_sold_lead['state'],$last_sold_lead['price'],$product,$channel_id);
            if ($good_customer){

                $good_customer_data = T3DB::apiReplicant()->fetchRow("select * from leads_data_$product where id=$good_customer");
                $last_sold_lead_data = T3DB::apiReplicant()->fetchRow("select * from leads_data_$product where id=".$last_sold_lead['id']);

                $lead_data = $last_sold_lead_data;

                $pay_dates = $this->get_pay_dates($last_sold_lead_data['pay_frequency'],$product);

                $email = $this->free_email();

                $lead_data['email'] = $email['email'];
                $lead_data['pay_date1'] = $pay_dates['pay_date1'];
                $lead_data['pay_date2'] = $pay_dates['pay_date2'];

                foreach ($last_sold_lead_data as $key=>$value){
                    if (in_array($key,$client_fields[$product])){
                        $lead_data[$key] = $good_customer_data[$key];
                    }
                }

                 if ($product == 'payday'){
                     $lead_data['bank_account_number'] = T3Crypt::Decrypt($lead_data['bank_account_number']);
                     $lead_data['drivers_license_number'] = T3Crypt::Decrypt($lead_data['drivers_license_number']);
                     $lead_data['address'] = T3Crypt::Decrypt($lead_data['address']);
                     $lead_data['first_name'] = T3Crypt::Decrypt($lead_data['first_name']);
                     $lead_data['last_name'] = T3Crypt::Decrypt($lead_data['last_name']);
                 }else if ($product == 'ukpayday'){
                     $lead_data['first_name'] = T3Crypt::Decrypt($lead_data['first_name']);
                     $lead_data['last_name'] = T3Crypt::Decrypt($lead_data['last_name']);
                 }else if ($product == 'capayday'){
                     $lead_data['first_name'] = T3Crypt::Decrypt($lead_data['first_name']);
                     $lead_data['last_name'] = T3Crypt::Decrypt($lead_data['last_name']);
                 }

                unset($lead_data['id']);

                $lead_data['ip'] = T3DB::api()->fetchOne("select ip_address from leads_data where id=".$good_customer_data['id']);

                $data = array(
                    'body'=>serialize($lead_data)
                );
                T3DB::api()->insert('seeding_leads',$data);
                $lead_id = T3DB::api()->lastInsertId();

                $data = array(
                    'lead_id'=>$lead_id
                );
                T3DB::api()->update('seeding_emails',$data,"id=".$email['id']);

                $data = array(
                    'channel_id'=>$channel_id,
                    'lead_id'=>$lead_id,
                    'date_add'=>date("Y-m-d H:i:s"),
                    'call'=>0,
                    'sms'=>0,
                    'email'=>0
                );
                T3DB::api()->insert('seeding_task',$data);

                $task_id = T3DB::api()->lastInsertId();

                return $task_id;
            }
        }
        return false;
    }

    public function shedule_task($task_id,$phone=true,$lead=null){
        /*
        $task = T3DB::api()->fetchRow("select * from seeding_task where id=".$task_id);

        $product = T3DB::api()->fetchOne("select `product` from buyers_channels where id=".$task['channel_id']);

        if ($lead){
            $data = array(
                'body'=>serialize($lead)
            );
            T3DB::api()->update('seeding_leads',$data,"id=".$task['lead_id']);
        }
        $lead = unserialize(T3DB::api()->fetchOne("select `body` from `seeding_leads` where `id`=".$task['lead_id']));
        if ($phone){
            if ($product == 'payday'){
                $region = $lead['state'];
                $country = 'US';
            }else if ($product == 'ukpayday'){
                $region = $lead['county'];
                $country = 'GB';
            }else if ($product == 'capayday'){
                $region = $lead['province'];
                $country = 'CA';
            }

            $twilio = new T3Twilio();
            $phone_num = $twilio->get_phone($country,$region,$task['channel_id']);
            if (!$phone_num){
                $phone_num = $twilio->get_phone($country,null,$task['channel_id']);
            }
            if ($phone_num){
                if ($product == 'payday' || $product == 'capayday'){
                    $phone_iso = "+1".$phone_num;
                    $lead['home_phone'] = $phone_num;
                    $lead['cell_phone'] = $phone_num;
                }else if ($product == 'ukpayday'){
                    $phone_iso = "+44".$phone_num;
                    $lead['home_phone'] = '0'.$phone_num;

                    $phone_num_cell = $twilio->get_phone($country,null,$task['channel_id'],'Mobile');
                    if ($phone_num_cell){
                        $lead['cell_phone'] = '0'.$phone_num_cell;
                        $phone_num_cell_iso = "+44".$phone_num_cell;

                        $data = array(
                            'phone'=>$phone_num_cell,
                            'phone_iso'=>$phone_num_cell_iso,
                            'date_add'=>date("Y-m-d H:i:s"),
                            'lead_id'=>$task['lead_id']
                        );
                        T3DB::api()->insert('seeding_phones',$data);


                    }else{
                        $lead['cell_phone'] = '0'.$phone_num;
                    }
                }

                $data = array(
                    'body'=>serialize($lead)
                );
                T3DB::api()->update('seeding_leads',$data,"id=".$task['lead_id']);

                $data = array(
                    'phone'=>$phone_num,
                    'phone_iso'=>$phone_iso,
                    'date_add'=>date("Y-m-d H:i:s"),
                    'lead_id'=>$task['lead_id']
                );
                T3DB::api()->insert('seeding_phones',$data);
            }
        }

        $data = array(
            'date_willsend'=>date("Y-m-d H:i:s")
        );
        T3DB::api()->update('seeding_task',$data,"id=".$task_id);

        return $data['date_willsend'];
        */
    }

    public function send(){
        $leads = T3DB::api()->fetchAll("select seeding_task.id,seeding_task.channel_id,seeding_task.lead_id,seeding_leads.body,buyers_channels.product from seeding_task,seeding_leads,buyers_channels where seeding_task.lead_id=seeding_leads.id and seeding_task.channel_id=buyers_channels.id and buyers_channels.filter_datetime=1 and (seeding_task.date_sent is null or seeding_task.date_sent='0000-00-00 00:00:00') and seeding_task.date_willsend<NOW()");
        foreach ($leads as $lead){
            $body = unserialize($lead['body']);

            $testLead = new T3Lead($lead['product']);
            $testLead->id = '999999';
            $testLead->affid = '3000';
            if (isset($body['ip']) && $body['ip']>0){
                $testLead->ip_address = $body['ip'];
            }else{
                $testLead->ip_address = myHttp::get_ip_num('76.9.31.146');
            }
            $testLead->data_email = $body['email'];

            unset($body['ip']);

            foreach ($body as $key=>$value){
                $testLead->body->$key = $value;
            }


            $result = $testLead->postToBuyer($lead['channel_id'], true);

            $obj_params = get_object_vars($result);
            if (isset($obj_params['system']))
                unset($obj_params['system']);
            if (isset($obj_params['database']))
                unset($obj_params['database']);
            if (isset($obj_params['tables']))
                unset($obj_params['tables']);

            $log = serialize($obj_params['sendLog']);
            $status = $obj_params['status'];

            $data = array(
                'task_id' => $lead['id'],
                'status'  => $status,
                'log'     => $log,
            );
            T3DB::api()->insert('seeding_logs',$data);

            $data = array(
                'date_sent'=>date("Y-m-d H:i:s")
            );
            T3DB::api()->update('seeding_task',$data,"id=".$lead['id']);

            $data = array(
                'date_send'=>date("Y-m-d H:i:s")
            );
            T3DB::api()->update('seeding_emails',$data,"lead_id=".$lead['lead_id']);
        }
    }

    public static function countnewcalls($channel_id=null){
        if ($channel_id > 0){
            return T3DB::api()->fetchOne("select count(seeding_phones.id) from seeding_phones,seeding_task,seeding_calls where seeding_phones.lead_id=seeding_task.lead_id and seeding_calls.phone_id=seeding_phones.id and seeding_calls.new=1 and seeding_task.channel_id=$channel_id");
        }else{
            return T3DB::api()->fetchOne("select count(id) from seeding_calls where new=1");
        }
    }

    public static function countnewsms($channel_id=null){
        if ($channel_id > 0){
            return T3DB::api()->fetchOne("select count(seeding_phones.id) from seeding_phones,seeding_task,seeding_sms where seeding_phones.lead_id=seeding_task.lead_id and seeding_sms.phone_id=seeding_phones.id and seeding_sms.new=1 and seeding_task.channel_id=$channel_id");
        }else{
            return T3DB::api()->fetchOne("select count(id) from seeding_sms where new=1");
        }
    }

    public static function countnewemails($channel_id=null){
        if ($channel_id > 0){
            return T3DB::api()->fetchOne("select count(seeding_emails.id) from seeding_emails,seeding_task,seeding_emailslist where seeding_emails.lead_id=seeding_task.lead_id and seeding_emailslist.email_id=seeding_emails.id and seeding_emailslist.new=1 and seeding_task.channel_id=$channel_id");
        }else{
            return T3DB::api()->fetchOne("select count(id) from seeding_emailslist where new=1");
        }
    }

    public static function readcall($id){
        $data = array(
            'new'=>1
        );
        T3DB::api()->update('seeding_calls',$data,"id=$id");
    }

    public static function readsms($id){
        $data = array(
            'new'=>1
        );
        T3DB::api()->update('seeding_sms',$data,"id=$id");
    }

    public static function reademail($id){
        $data = array(
            'new'=>1
        );
        T3DB::api()->update('seeding_emailslist',$data,"id=$id");
    }

}

