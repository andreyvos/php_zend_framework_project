<?php

TableDescription::addTable('revnet_leads', array(
    'id',
    'create_date',
    'webmaster',
    'product',
    'lead_id',
    'account',
    'data_email',
    'data_phone',
    'per_rev',
    'per_wm',
    'rev',
    'wm',
    'ttl',
    'get_price'
));

class T3RevNet_Lead extends DbSerializable{
    public $id;
    public $create_date;
    public $webmaster; 
    public $product; 
    public $lead_id;
    public $account;
    public $data_email;
    public $data_phone;
    public $per_rev;
    public $per_wm;
    public $rev = 0;
    public $wm = 0;
    public $ttl = 0;
    public $get_price = 0; 

    
    /**
    * Объект лида
    * 
    * @var T3Lead
    */
    protected $lead;

    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('revnet_leads');
    }
    
    
    public function getLead($lazy = true){
        if(!$lazy || is_null($this->lead)){
            $this->lead = new T3Lead();
            $this->lead->fromDatabase($this->lead_id);
            $this->lead->getBodyFromDatabase();
        } 
        return $this->lead;   
    }
    
    public function setLead(T3Lead $lead){
        $this->lead = $lead;    
    }
    
    protected function getPostURL(){
        //return "http://system.t3leads.com/system/revnet/test_response.php";   
        //return "http://t3.lh/system/revnet/test_response.php"; 
        return "https://intake.revnet.com/PostBDataCollection.aspx";   
    }
    
    public function postAsync(){
        //$host = 't3.lh';
        $host = 'system.t3leads.com'; 
        
        $fp = fsockopen($host, 80, $errno, $errstr, 5);
        if ($fp) {
            $out = "GET /system/revnet/post.php?id=" . urlencode($this->id) . " HTTP/1.1\r\n";
            $out .= "Host: {$host}\r\n\r\n";
            fwrite($fp, $out);
            fclose($fp);
        }    
    }
    
    public function getPost(){
        return T3Db::api()->fetchAll("select * from revnet_post_log where id_revnet_lead=?", $this->id);    
    }
    
    public function getTracks(){
        return T3Db::api()->fetchAll("select * from revnet_track where leadId=?", $this->id);    
    }
    
    
    
    public function post(){     
        /**  
        * @var T3Lead
        */
        $lead =& $this->getLead();

        /** @var T3LeadBody_PersonalLoan */
        $body =& $lead->getBody();

        // отправка лида на RevNet 
        $a = array();

        $url = "https://intake.revnet.com/PostBDataCollection.aspx";

        // Наш ID вебмастра, в системе у ревнета
        $revnetCID = '328386321';
        
        // Для лидов с вебастера t3radio (37631) делаем другой ID
        if($this->webmaster == '37631'){
            $revnetCID = '379684971';    
        }
        
        if($this->account == T3RevNet_Leads::account_NotSoldOnlyPhone){
            $url = "https://intake.revnet.com/PostCDataCollection.aspx";

            $a['ac']    = $revnetCID;
            $a['fn']    = $body->first_name;
            $a['ln']    = $body->last_name;
            $a['phn']   = $body->home_phone;
            $a['cat']   = "1";
        }
        else {
            if(in_array($this->product, array('payday', 'payday_optin'))){
                $a['ac']    = $revnetCID;
                $a['cat']   = "1";
                $a['web']   = "www.ameriadvance.com";
                $a['lid']   = $this->id; // ID R лида (для него это будет 1 2 3 4 5)
                //$a['si']    = T3RevNet_Settings::getAccountSetting($this->account, 'postSubaccount'); // откуда мы этот лид сформировали
                $a['si']    = '';

                if($this->account == T3RevNet_Leads::account_NotSoldTime){
                    $a['dl']    = T3Db::api()->fetchOne("select days from revnet_time_days where id=?", $this->id);
                }

                $a['ip']    = myHttp::get_ip_str($lead->ip_address);
                $a['cnt']   = "US";
                $a['st']    = $body->state;
                $a['fn']    = $body->first_name;
                $a['ln']    = $body->last_name;
                $a['eml']   = $body->email;
                $a['phn']   = $body->home_phone;
                $a['zip']   = $body->zip;
                $a['ad1']   = $body->address;
                $a['cty']   = $body->city;
                $a['hu']    = $body->payday_array_own_home("own", "rent");
                //$a['wiu']   = "yes"; // ??????????????????????
                $a['btc']   = strtolower($body->best_time_to_call);
                $a['mic']   = $body->monthly_income;
                $a['hba']   = $body->bank_account_type;
                $a['dd']    = $body->payday_array_direct_deposit("yes", "no");
                $a['pp']    = $body->payday_array_pay_frequency("weekly", 'bi_weekly', 'twice_monthly', 'monthly');
                $a['npd']   = $body->date_mdY($body->pay_date1);
                $a['spd']   = $body->date_mdY($body->pay_date2);
                $a['rla']   = $body->requested_amount;
                //$a['mar']   = "2"; // ??????????????????????
                $a['ity']   = $body->payday_array_income_type("employment", "benefits");
                $a['acm']   = $body->payday_array_active_military("yes", "no");
                $a['ocu']   = $body->job_title;
                $a['emp']   = $body->employer;
                $a['wph']   = $body->work_phone;
                //$a['mem']   = "24"; // ??????????????????????
                $a['bn']    = $body->bank_name;
                $a['acno']  = $body->bank_account_number;
                $a['runo']  = $body->bank_aba;
                $a['bph']   = $body->bank_phone;
                $a['lcst']  = $body->drivers_license_state;
                $a['lcno']  = $body->drivers_license_number;
                $a['mmn']   = $body->mother_maiden_name;
                $a['dob']   = $body->date_mdY($body->birth_date);
                $a['ssn']   = $body->ssn;
                $a['r1fn']  = $body->reference1_first_name;
                $a['r1ln']  = $body->reference1_last_name;
                $a['r1rl']  = $body->payday_array_relationship1('parent', 'sibling', 'friend', 'relative');
                $a['r1ph']  = $body->reference1_phone;
                $a['r2fn']  = $body->reference2_first_name;
                $a['r2ln']  = $body->reference2_last_name;
                $a['r2rl']  = $body->payday_array_relationship2('parent', 'sibling', 'friend', 'relative');
                $a['r2ph']  = $body->reference2_phone;
                //$a['mab']   = "12"; // ??????????????????????
                $a['cel']   = $body->cell_phone;
            }
            else if(in_array($this->product, array('ukpayday', 'uk_payday_optin'))){
                $a['ac']    = $revnetCID;
                $a['cat']   = "19";
                $a['lid']   = $this->id; // ID R лида (для него это будет 1 2 3 4 5)
                $a['si']    = '';
                
                if($this->account == T3RevNet_Leads::account_UKPaydayNotSoldTime){
                    $a['dl']    = T3Db::api()->fetchOne("select days from revnet_time_days where id=?", $this->id);     
                }

                $a['ip']    = myHttp::get_ip_str($lead->ip_address); // lead_ip
                $a['cnt']   = "GB";
                $a['fn']    = $body->first_name;
                $a['ln']    = $body->last_name;
                $a['eml']   = $body->email;
                
                $a['ti']    = $body->title; // title    Varchar 5
                $a['dob']   = $body->date_mdY($body->birth_date); // dob    Mm/dd/yyyy
                
                $a['hs']    = $body->streetnumber; // house    Varchar
                $a['str']   = $body->streetname; // street    Varchar
                $a['cou']   = $body->county; // county    Varchar
                $a['cty']   = $body->town; // city    Varchar
                $a['zip']   = $body->postcode; // zip    Varchar(8) 
                $a['hu']    = $body->ukpayday_array_own_home("own", "rent"); // housing    rent/own
                
                $a['phn']   = $body->home_phone; // home_phone    Numeric 11
                $a['cel']   = $body->cell_phone; // mobile_phone    Numeric 11
                
                $a['rd']    = $body->address_length_year*12 + $body->address_length_months; // residence_duration    Numeric Months
                $a['ity']   = $body->income_type; // income_source    Varchar (30)
                $a['mic']   = $body->monthly_income; // monthly_income    Numeric
                $a['pp']    = $body->pay_frequency; // pay_period    Varchar (50)
                $a['dd']    = $body->ukpayday_array_direct_deposit('yes', 'yes', 'no'); // direct_deposit    yes/no 
                $a['npd']   = $body->date_mdY($body->pay_date1); // next_pay_date    mm/dd/yyyy
                $a['spd']   = $body->date_mdY($body->pay_date2); // second_pay_date    mm/dd/yyyy
                $a['rla']   = $body->requested_amount; // requested_loan_amount    NUmeric
                $a['jt']    = $body->job_title; // job_title    Varchar
                $a['emp']   = $body->employer; // employer    Varchar
                $a['wph']   = $body->work_phone; // work_phone    Numeric 11 
                $a['ed']    = $body->employed_years*12 + $body->employed_months; // employment_duration    Numeric Months  
                $a['dc']    = $body->debit_card; // debit_card    VarChar (20)
                $a['acno']  = $body->bank_account_number; // account_number    Numeric (Max 30)
                $a['acdu']  = $body->bank_account_length_months; // account_duration    Numeric Months
                $a['sc']    = $body->bank_sort_code; // sort_code    Numeric (Max 30)
                $a['nin']   = ''; // nin    Varchar 9 
                $a['bn']    = $body->bank_name; // bank_name    Varchar
                $a['acty']  = $body->bank_account_type; // account_type    VarChar    
            }
            else if(in_array($this->product, array('capayday', 'capayday_optin'))){
                $a['ac']    = $revnetCID;
                $a['cat']   = "31";          
                $a['lid']   = $this->id; // ID R лида (для него это будет 1 2 3 4 5)                
                $a['si']    = '';
                
                if($this->account == T3RevNet_Leads::account_CanadaNotSoldTime){
                    $a['dl']    = T3Db::api()->fetchOne("select days from revnet_time_days where id=?", $this->id);     
                }
                
                $a['ip']    = myHttp::get_ip_str($lead->ip_address);
                
                $a['rla']   = $body->requested_amount; // requested_amount
                $a['ti']    = $body->title; // title
                $a['sin']   = $body->sin; // sin
                $a['fn']    = $body->first_name; // first_name
                $a['ln']    = $body->last_name; // last_name
                $a['dob']   = $body->birth_date; // birth_date
                $a['eml']   = $body->email; // email
                $a['phn']   = $body->home_phone; // home_phone
                $a['wph']   = $body->work_phone; // work_phone
                $a['cell']  = $body->cell_phone; // cell_phone
                $a['adr']   = $body->address; // address
                $a['cty']   = $body->city; // city
                $a['pro']   = $body->province; // province
                $a['zip']   = $body->postcode; // postcode
                $a['rd']    = $body->address_length_months; // address_length_months
                $a['hu']    = $body->own_home; // own_home
                $a['ity']   = $body->income_type; // income_type
                $a['emp']   = $body->employer; // employer
                $a['jt']    = $body->job_title; // job_title
                $a['ed']    = $body->employed_months; // employed_months
                $a['mic']   = $body->monthly_income; // monthly_income
                $a['pp']    = $body->pay_frequency; // pay_frequency
                $a['npd']   = $body->pay_date1; // pay_date1
                $a['spd']   = $body->pay_date2; // pay_date2
                $a['ead']   = $body->employer_address; // employer_address
                $a['ect']   = $body->employer_city; // employer_city
                $a['epr']   = $body->employer_province; // employer_province
                $a['ezp']   = $body->employer_postcode; // employer_postcode
                $a['bin']   = $body->bank_institution_number; // bank_institution_number
                $a['bn']    = $body->bank_name; // bank_name
                $a['brn']   = $body->bank_branch_number; // bank_branch_number
                $a['acno']  = $body->bank_account_number; // bank_account_number
                $a['acdu']  = $body->bank_account_length_months; // bank_account_length_months
                $a['dd']    = $body->direct_deposit; // direct_deposit
                $a['acty']  = $body->bank_account_type; // bank_account_type
            }
            else if(in_array($this->product, array('personalloan'))){
                $a['ac']    = $revnetCID;
                $a['cat']   = "42";
                $a['web']   = "www.ameriadvance.com";
                $a['lid']   = $this->id; // ID R лида (для него это будет 1 2 3 4 5)
                $a['si']    = '';

                if($this->account == T3RevNet_Leads::account_UsaPersonalLoanNotSoldTime){
                    $a['dl']    = T3Db::api()->fetchOne("select days from revnet_time_days where id=?", $this->id);
                }

                $a['ip']    = myHttp::get_ip_str($lead->ip_address);
                $a['cnt']   = "US";
                $a['st']    = $body->state;
                $a['fn']    = $body->first_name;
                $a['ln']    = $body->last_name;
                $a['eml']   = $body->email;
                $a['phn']   = $body->home_phone;
                $a['zip']   = $body->zip;
                $a['ad1']   = $body->address;
                $a['cty']   = $body->city;
                $a['hu']    = $body->payday_array_own_home("own", "rent");
                //$a['wiu']   = "yes"; // ??????????????????????
                $a['btc']   = strtolower($body->best_time_to_call);
                $a['mic']   = $body->monthly_income;
                $a['hba']   = $body->bank_account_type;
                $a['dd']    = $body->payday_array_direct_deposit("yes", "no");
                $a['pp']    = $body->payday_array_pay_frequency("weekly", 'bi_weekly', 'twice_monthly', 'monthly');
                $a['npd']   = $body->date_mdY($body->pay_date1);
                $a['spd']   = $body->date_mdY($body->pay_date2);
                $a['rla']   = $body->requested_amount;
                //$a['mar']   = "2"; // ??????????????????????
                $a['ity']   = $body->payday_array_income_type("employment", "benefits");
                $a['acm']   = $body->payday_array_active_military("yes", "no");
                $a['ocu']   = $body->job_title;
                $a['emp']   = $body->employer;
                $a['wph']   = $body->work_phone;
                //$a['mem']   = "24"; // ??????????????????????
                $a['bn']    = $body->bank_name;
                $a['acno']  = $body->bank_account_number;
                $a['runo']  = $body->bank_aba;
                $a['bph']   = $body->bank_phone;
                $a['lcst']  = $body->drivers_license_state;
                $a['lcno']  = $body->drivers_license_number;
                $a['mmn']   = $body->mother_maiden_name;
                $a['dob']   = $body->date_mdY($body->birth_date);
                $a['ssn']   = $body->ssn;
                $a['r1fn']  = $body->reference1_first_name;
                $a['r1ln']  = $body->reference1_last_name;
                $a['r1rl']  = $body->payday_array_relationship1('parent', 'sibling', 'friend', 'relative');
                $a['r1ph']  = $body->reference1_phone;
                $a['r2fn']  = $body->reference2_first_name;
                $a['r2ln']  = $body->reference2_last_name;
                $a['r2rl']  = $body->payday_array_relationship2('parent', 'sibling', 'friend', 'relative');
                $a['r2ph']  = $body->reference2_phone;
                //$a['mab']   = "12"; // ??????????????????????
                $a['cel']   = $body->cell_phone;
                $a['cs']    = $body->credit_grade;
            }
        }


        if(count($a)){
            // посстроение строки запроса
            $data = "";
                    
            if(isset($a) && is_array($a) && count($a)){
                foreach($a as $var => $val){
                    if(strlen($data))$data.= "&";
                    $data.= "{$var}=" . urlencode($val);        
                }    
            }
              
            $head = array(
                'Expect: ',
            );
               
            // настрйока отправки  
            $ch = curl_init();

            curl_setopt(    $ch,    CURLOPT_URL,               $url                            );
            curl_setopt(    $ch,    CURLOPT_POST,              0                               );
            curl_setopt(    $ch,    CURLOPT_POSTFIELDS,        $data                           );    
            curl_setopt(    $ch,    CURLOPT_HTTPHEADER,        $head                           ); 
            curl_setopt(    $ch,    CURLOPT_FAILONERROR,       1                               );
            curl_setopt(    $ch,    CURLOPT_HEADER,            1                               );
            curl_setopt(    $ch,    CURLOPT_RETURNTRANSFER,    1                               );
            curl_setopt(    $ch,    CURLOPT_SSL_VERIFYPEER,    false                           );
            curl_setopt(    $ch,    CURLOPT_SSL_VERIFYHOST,    false                           );
            curl_setopt(    $ch,    CURLOPT_TIMEOUT,           120                             );
            curl_setopt(    $ch,    CURLINFO_HEADER_OUT,       true                            );
            curl_setopt(    $ch,    CURLOPT_HTTP_VERSION,      CURL_HTTP_VERSION_1_1           );

            $startDate = date('Y-m-d H:i:s');
            
            // отправка данных, получение ответа, отделение header от body($return)
            $output         = curl_exec($ch);
            $header_size    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers        = substr($output, 0, $header_size - 4);
            $return         = substr($output, $header_size);

            $request_header = curl_getinfo($ch, CURLINFO_HEADER_OUT);


            $request_body = $data;
            if ($this->product == 'payday' || $this->product == 'personalloan'){
                $ssn = ifset($body->ssn);
                if (strlen($ssn)>0){
                    $ssn_crypt = T3SSN::encrypt($ssn);
                    $request_body = str_replace($body->ssn,$ssn_crypt['hash'],$request_body);
                }
            }

            $result = array(
                'date_post'         => $startDate,
                'id_revnet_lead'    => $this->id,
                'account'           => $this->account,
                'request_header'    => $request_header,
                'request'           => $request_body,
                'response_header'   => $headers,
                'response'          => $return,
                'seconds'           => round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 1),
                'status'            => 'unknown',
                'reason'            => ''
            );

            // анализ отправки
            if(curl_errno($ch)){              
                // Ошибка
                $result['status'] = 'post_error';
                $result['reason'] = curl_error($ch);  
                   
            }
            else {                                 
                // Отправка завершена успешно, надо анализировать ответ
                $result['status'] = 'success';     
                // $result['status'] = 'error'; 
                // $result['status'] = 'reject'; 
                curl_close($ch);
            }
            
            T3Db::api()->insert("revnet_post_log", $result);
        }   
    }
}

