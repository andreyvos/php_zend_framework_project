<?php

class T3BuyerTest {

    private $database;

    private static $_instance = null;

    public function __construct(){
        $this->database = T3Db::api();
    }

    public static function getInstance(){
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function GetFreeEmail(){
        $sql = "select id,email from buyers_test_email where id not in (select email_id from buyers_test) limit 1";
        return self::getInstance()->database->fetchRow($sql);
    }

    public static function GetEmail($id){
        $sql = "select id,email,password from buyers_test_email where id='$id'";
        return self::getInstance()->database->fetchRow($sql);
    }

    public static function GetPhoneNumber($id){
        $sql = "select phone from buyers_test_phone where id='$id'";
        return self::getInstance()->database->fetchOne($sql);
    }

    public function getPrivateData(){
        $sql = "select * from buyers_test_personaldata where id='".rand(20,2000)."'";
        return self::getInstance()->database->fetchRow($sql);
    }

    public static function getPrivateDataById($id){
        $sql = "select * from buyers_test_personaldata where id='".$id."'";
        return self::getInstance()->database->fetchRow($sql);
    }

    public function getPhone($state,$channel_id){
        $sql = "select * from buyers_test_phone where id not in (SELECT phone_id FROM buyers_test WHERE (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(`date`))<1209600 OR channel_id=$channel_id) and state='$state'";
        return self::getInstance()->database->fetchRow($sql);
    }

    public function getPhones(){
        $sql = "select * from buyers_test_phone";
        return self::getInstance()->database->fetchAll($sql);
    }

    public function addPhone($phone){
        $sql = "insert into buyers_test_phone (phone,date) values ('$phone',NOW());";
        self::getInstance()->database->query($sql);
    }

    public function countPhone(){
        $sql = "select count(id) from buyers_test_phone";
        return self::getInstance()->database->fetchOne($sql);
    }

    public function getLastLead($NotLessThenNDaysAgo,$channel_id){
        
        if ($NotLessThenNDaysAgo > 0){
            $maxtime = time()-86400*$NotLessThenNDaysAgo;
            $where="buyer_channel_id='$channel_id' and unix_timestamp(record_datetime)<$maxtime and lead_id<>999999 and post_result_status in ('Sold', 'Rejected') order by id desc limit 1";
        }else{
            $where="buyer_channel_id='$channel_id' and lead_id<>999999 and post_result_status in ('Sold', 'Rejected') order by id desc limit 1";
        }

        $sql=sprintf("select lead_id from buyers_statistics_lite where %s",$where);
        $id = T3Db::apiSlave()->fetchOne($sql);
        if (isset($id) && $id>1){
            return $id;
        }else{
            return 0;
        }
    }
    
    public function sendbyid($id,$lead_id,$tasklead_id){
        $result = '';
        
        $testlead = self::getInstance()->database->fetchRow("select * from buyers_test where id=$id");
        $testlead_task = self::getInstance()->database->fetchRow("select * from buyers_test_leads where test_id=$id and lead_id=$lead_id");
        
        $channel_id = $testlead['channel_id'];
        $personal_data = $this->getPrivateDataById($testlead['personaldata_id']);
        $phone = $this->GetPhoneNumber($testlead['phone_id']);
        $email = $this->GetEmail($testlead['email_id']);

        
        $mktime = mktime(0, 0, 0, date("m"), date("d")+1, date("Y"));
        $dateW = date("w",$mktime);
        if($dateW == "6" || $dateW == "0")$mktime += 86400*3;

        $lead = new T3Lead();
        $lead->fromDatabase($lead_id);
        $lead->getBodyFromDatabase();
        $lead->body->email = $email['email'];
        $lead->body->home_phone = $phone;
        
        
        if (isset($testlead_task['type']) && $testlead_task['type'] == 1){
            $lead->body->first_name = $personal_data['fname'];
            $lead->body->last_name = $personal_data['lname'];
            $lead->body->ssn = $personal_data['ssn'];
            
            $lead->body->bank_aba = $personal_data['aba'];
            $lead->body->bank_account_number = $personal_data['account'];
            $lead->body->bank_name = $personal_data['bank'];
            $lead->body->drivers_license_state = $personal_data['drv_state'];
            $lead->body->drivers_license_number = $personal_data['drv_num'];
            
            $lead->ip_address = $personal_data['ip'];
            
        }
        
        $lead->body->pay_date1 = date("Y-m-d", $mktime);
        if ($lead->body->pay_frequency == 'BIWEEKLY' || $lead->body->pay_frequency == 'TWICEMONTHLY'){
            $lead->body->pay_date2 = date("Y-m-d", $mktime + 86400*14);
        }else if ($lead->body->pay_frequency == 'WEEKLY'){
            $lead->body->pay_date2 = date("Y-m-d", $mktime + 86400*7);
        }else{
            $lead->body->pay_date2 = date("Y-m-d", $mktime + 86400*28);
        }

        $isTest = true;

        $postResult = $lead->postToBuyer($channel_id, $isTest);
        $sendLog = mysql_escape_string(varExportSafe($postResult->sendLog));
        $sql = "update buyers_test set status='{$postResult->status}',log='$sendLog',`date`=NOW() where id='$id'";
        $result = $postResult->status;
        self::getInstance()->database->query($sql);
        $sql = "update buyers_test_leads set is_send='1' where id='$tasklead_id'";
        self::getInstance()->database->query($sql);

        return $result;
    }
    
    

    public function send($channel_id){
        $result = '';
        $personal_data = $this->getPrivateData();
        $lead_id = $this->getLastLead(14, $channel_id);
        
        $phone = $this->getPhone();
        $phone_id = 0;
        $phone_num = $personal_data['phone'];
        if (isset($phone) || (int)$phone['id']>1){
            $phone_id = $phone['id'];
            $phone_num = $phone['phone'];
        }
        $email = $this->GetFreeEmail();
        if (!isset($email) || (int)$email['id']<1){
            $result = 'There are no free emails';
        }else{            
            if ($lead_id>0 && $lead_id<>'999999'){
                $sql = "insert into buyers_test (date,channel_id,email_id,phone_id,personaldata_id) values (NOW(),'$channel_id','{$email['id']}','$phone_id','{$personal_data['id']}')";
                self::getInstance()->database->query($sql);
                $testid = self::getInstance()->database->lastInsertId();

                $mktime = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                $dateW = date("w",$mktime);
                if($dateW == "6" || $dateW == "0")$mktime += 86400*3;

                $lead = new T3Lead();
                $lead->fromDatabase($lead_id);
                $lead->getBodyFromDatabase();
                $lead->body->email = $email['email'];
                $lead->body->first_name = $personal_data['fname'];
                $lead->body->last_name = $personal_data['lname'];
                $lead->body->ssn = $personal_data['ssn'];
                $lead->body->home_phone = $phone_num;
                $lead->body->pay_date1 = date("Y-m-d", $mktime);
                if ($lead->body->pay_frequency == 'BIWEEKLY' || $lead->body->pay_frequency == 'TWICEMONTHLY'){
                    $lead->body->pay_date2 = date("Y-m-d", $mktime + 86400*14);
                }else if ($lead->body->pay_frequency == 'WEEKLY'){
                    $lead->body->pay_date2 = date("Y-m-d", $mktime + 86400*7);
                }else{
                    $lead->body->pay_date2 = date("Y-m-d", $mktime + 86400*28);
                }

                $isTest = true;

                $postResult = $lead->postToBuyer($channel_id, $isTest);
                $sendLog = mysql_escape_string(varExportSafe($postResult->sendLog));
                $sql = "update buyers_test set status='{$postResult->status}',log='$sendLog' where id='$testid'";
                $result = $postResult->status;
                self::getInstance()->database->query($sql);

            }else{
                $result = 'Lead not found';
            }
        }

        return $result;
    }
    
    public function getLastLeadsByChannelId($states,$channel_id,$count,$NotLessThenNDaysAgo,$not_id=array()){
        $states_str = ''; 
        foreach ($states as $item){
            $states_str.="'$item',";    
        }
        $states_str = substr($states_str,0,strlen($states_str)-1); 
        $maxtime = time()-86400*$NotLessThenNDaysAgo;
        
        $minprice = (int)T3Db::apiSlave()->fetchOne("select minConstPrice from buyers_channels where id=$channel_id");
        
        if ($minprice<120){
        $min_val = $minprice+10;
        $max_val = $minprice+50;
        }else{
            $min_val = $minprice-15;
            $max_val = $minprice+50;    
        }
        
        $where="buyers_statistics_lite.buyer_channel_id in (SELECT id FROM buyers_channels WHERE product='payday' AND `status`='active' AND minConstPrice between $min_val and $max_val and id<>$channel_id) and unix_timestamp(buyers_statistics_lite.record_datetime)<$maxtime and buyers_statistics_lite.lead_id<>999999 and buyers_statistics_lite.post_result_status='Sold' and buyers_statistics_lite.lead_id not in (select lead_id from buyers_test_leads) and leads_data.data_state in ($states_str) and leads_data.id=buyers_statistics_lite.lead_id order by buyers_statistics_lite.id desc limit $count";
        $not_leads = '';
        if (is_array($not_id) && count($not_id)>0){
            foreach ($not_id as $item){
                $not_leads.=$item.',';    
            }    
        }
        $not_leads = substr($not_leads,0,strlen($not_leads)-1);
        if (strlen($not_leads)){
            $where = "buyers_statistics_lite.lead_id not in ($not_leads) and ".$where;    
        }
        
        $sql=sprintf("select buyers_statistics_lite.lead_id,leads_data.data_state from buyers_statistics_lite,leads_data where %s",$where);
        
        $id = T3Db::apiSlave()->fetchAll($sql);
        if ($id){
            return $id;
        }else{
            return false;
        }        
    }
    
    public function checkData($channel_id,$data){
        $filter = new T3BuyerFilter();
        $filter->fromDatabase($channel_id);
        
        if(!$filter->getCondition('Date')->works)
            return true;
        return T3BuyerFilter_Condition_Date::posting_filter__dataRun(
                $filter->getCondition('Date')->misc, date('Y-m-d H:i:s',$data)
                );    
    }
    
    public function addTask($channel_id,$data,$count,$interval,$sendtype){        
        try {
            $avialable_states = $this->GetAvialableState();
            $leadObject = T3Lead::createTestLead('payday');
            $filter = T3BuyerFilters::getInstance()->getFilter($channel_id);
            $cur_state = array();        
            foreach ($avialable_states as $item){
                $state = $item['state'];
                $leadObject->body->state = $state; 
                if(!$filter->getCondition('States')->works || $filter->getCondition('States')->acceptsLead($leadObject)){
                    $cur_state[] = $state;    
                }
            }
            
            if (count($cur_state)==0){
                return 'phone number not found';
            }
            
            $count_comming = 0;
            $iteracii = 0;
            $not_leads = array();
            $leads = array();
            while ($count_comming<$count && $iteracii<10){
                $leads_first = $this->getLastLeadsByChannelId($cur_state,$channel_id,50,14,$not_leads);
                foreach ($leads_first as $item){
                    if ($count_comming<$count){
                        $not_leads[] =  $item['lead_id'];
                        
                        $leadObject = new T3Lead();
                        
                        $lead_id = self::getInstance()->database->fetchOne("select getId from leads_data where id=".$item['lead_id']);
                        
                        $leadObject->loadFromGetID($lead_id);
                        $leadObject->getBodyFromDatabase();
                        $filter->lastErrorCondition;
                        $filterResult = $filter->acceptsLead( $leadObject );
                        if(!$filterResult->isError() || ($filterResult->messages[count($filterResult->messages)-1]->subject == 'Date' && $filterResult->isError())){
                            if (count($leads)<$count){
                                $leads[] = $item;
                            }
                            $count_comming++;
                        }
                    }             
                }
                $iteracii++;    
            }

            if ($leads){
                if (count($leads)<$count){
                    $count = count($leads);
                }
                //добавляем задачу
                
                    
                $from_timestamp = date('U',strtotime($data));
                $now = date('U');
                if ($from_timestamp<$now){
                    $from_timestamp = $now+60*10;    
                }
                
                $sql = sprintf("insert into buyers_test_task (`buyer_channel_id`,`create_date`,`count`,`start_date`,`interval`) values ($channel_id,NOW(),$count,'%s',$interval);",date('Y-m-d H:i:s',$from_timestamp));
                self::getInstance()->database->query($sql);
                $task_id = self::getInstance()->database->lastInsertId();
      
                $i = 0;
                $j = 0;
                
                while($i<$count){
                    if ($this->checkData($channel_id,$from_timestamp)){
                        $lead_id = $leads[$i]['lead_id'];
                        $lead_state = $leads[$i]['data_state'];                    
                        $phone = $this->getPhone($lead_state,$channel_id);
                        $email = $this->GetFreeEmail();
                        $personal_data = $this->getPrivateData();
                        if ($phone){
                            $sql = "insert into buyers_test (date,channel_id,email_id,phone_id,personaldata_id) values (NOW(),'$channel_id','{$email['id']}','{$phone['id']}','{$personal_data['id']}')";
                            self::getInstance()->database->query($sql);
                            $test_id = self::getInstance()->database->lastInsertId();
                            
                            $sql = sprintf("insert into buyers_test_leads (task_id,test_id,lead_id,send_date,is_send,`type`) values ($task_id,$test_id,$lead_id,'%s',0,%s);",date('Y-m-d H:i:s',$from_timestamp),$sendtype);
                            self::getInstance()->database->query($sql);
                            $j++;
                        }
                        $i++;
                    }    
                    $from_timestamp = $from_timestamp+$interval*60;
                }
                if ($i>0){
                    $sql = "update buyers_test_task set `count`=$j where id=$task_id";
                    self::getInstance()->database->query($sql);
                    return $task_id;
                }else{
                    self::getInstance()->database->query("delete from buyers_test_task where id=$task_id");
                    return 'lead not found';    
                }    
            }else{
                return 'lead not found';
            }
        } catch (Exception $e) {
            return 'lead not found';
        }
                
    }
    
    public static function GetStateByPhone($phone){
        if (strlen($phone)<3){
            return false;
        }
        $areacode = substr($phone,0,3);
        return self::getInstance()->database->fetchOne("SELECT state FROM phone_areacode WHERE areacode=$areacode"); 
    }
    
    public static function GetAvialableState(){
        return self::getInstance()->database->fetchAll("select state from buyers_test_phone where id not in (select phone_id from buyers_test) group by state");    
    }
    
    public static function GetTestByPhone($phone_id) {
        $phone_id = (int)$phone_id;
        return self::getInstance()->database->fetchAll("select * from buyers_test where phone_id=$phone_id order by `date` desc"); 
    }
}



