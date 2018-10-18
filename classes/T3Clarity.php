<?php
  class T3Clarity {
      public static function getinfo(T3LeadBody_PaydayLoan $b){
          
        $_url = 'https://secure.clarityservices.com/inquiries/create_from_xml_test';
        $_timeout = 5;
          
        $xml = new SimpleXMLElement('<inquiry></inquiry>');

        $xml->addChild('first-name',$b->first_name);
        $xml->addChild('last-name',$b->last_name);
        $xml->addChild('social-security-number',$b->ssn);
        $xml->addChild('bank-account-number',$b->bank_account_number);
        $xml->addChild('bank-routing-number',$b->bank_aba);
        $xml->addChild('date-of-birth',$b->birth_date);
        $xml->addChild('bank-account-type',$b->payday_array_bank_account_type('Checking','Saving'));
        $xml->addChild('street-address-1',$b->address);
        $xml->addChild('city',$b->city);
        $xml->addChild('state',$b->state);
        $xml->addChild('zip-code',$b->zip);
        $xml->addChild('drivers-license-state',$b->drivers_license_state);
        $xml->addChild('drivers-license-number',$b->drivers_license_number);
        $xml->addChild('inquiry-purpose-type','AR');
        $xml->addChild('inquiry-tradeline-type','C1');
        $xml->addChild('email-address',$b->email);
        $xml->addChild('housing-status',$b->payday_array_own_home('Own','Rent'));
        $xml->addChild('work-phone',$b->work_phone);
        $xml->addChild('home-phone',$b->home_phone);
        $xml->addChild('cell-phone',$b->cell_phone);
        $xml->addChild('work-fax-number',null);
        $xml->addChild('work-phone-extension',null);
        $xml->addChild('occupation-type',null);
        $xml->addChild('months-at-current-employer',$b->employed_length_months());
        $xml->addChild('employer-name',$b->employer);
        $xml->addChild('employer-address',$b->employer_address);
        $xml->addChild('employer-city',$b->employer_city);
        $xml->addChild('employer-state',$b->employer_state);
        $xml->addChild('net-monthly-income',$b->monthly_income);
        $xml->addChild('date-of-next-payday',$b->pay_date1);
        $xml->addChild('pay-frequency',$b->payday_array_pay_frequency('Weekly','Biweekly','Twicemonthly','Monthly'));
        $xml->addChild('paycheck-direct-deposit',$b->payday_array_direct_deposit(1,0));
        $xml->addChild('reference-first-name',$b->reference1_first_name);
        $xml->addChild('reference-relationship',$b->reference1_relationship);
        $xml->addChild('reference-phone',$b->reference1_phone);
        $xml->addChild('middle-initial',null);
        $xml->addChild('months-at-address',$b->address_length_months());
        $xml->addChild('control-file-version-number',null);
        $xml->addChild('username','t3utility');
        $xml->addChild('password',null);
        $xml->addChild('group-id',251);
        $xml->addChild('account-id',403);
        $xml->addChild('location-id',511);
        $xml->addChild('pass-through-1',null);
        $xml->addChild('pass-through-2',null);
        $xml->addChild('pass-through-3',null);
        $xml->addChild('pass-through-4',null);
        $xml->addChild('pass-through-5',null);

        $data = $xml->asXML();
        
        $headValues = array(
            array('Expect', ''),    
        );

        $headValues[] = array('Content-type','text/xml; charset=utf-8');
        
        foreach($headValues as $opt){
            $head[] = $opt[0] . ": " . $opt[1];     
        }

        $ch = curl_init();

        curl_setopt(    $ch,    CURLOPT_URL,               $_url                           );
        curl_setopt(    $ch,    CURLOPT_POST,              0                               );
        curl_setopt(    $ch,    CURLOPT_POSTFIELDS,        $data                           );
        
        curl_setopt(    $ch,    CURLOPT_HTTPHEADER,        $head                           ); 
        curl_setopt(    $ch,    CURLOPT_FAILONERROR,       1                               );
        curl_setopt(    $ch,    CURLOPT_HEADER,            1                               );
        curl_setopt(    $ch,    CURLOPT_RETURNTRANSFER,    1                               );
        curl_setopt(    $ch,    CURLOPT_SSL_VERIFYPEER,    false                           );
        curl_setopt(    $ch,    CURLOPT_SSL_VERIFYHOST,    false                           );
        curl_setopt(    $ch,    CURLOPT_TIMEOUT,           $_timeout                       );
        curl_setopt(    $ch,    CURLINFO_HEADER_OUT,       true                            );
        curl_setopt(    $ch,    CURLOPT_HTTP_VERSION,      CURL_HTTP_VERSION_1_1           );
        
        
        // отправка данных, получение ответа, отделение header от body($return)
        $output         = curl_exec($ch);
        $header_size    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers        = substr($output, 0, $header_size - 4);
        $return         = substr($output, $header_size);
        
        $request_header = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $request_header = substr($request_header, 0, strlen($request_header)-4);
      }      
  }
