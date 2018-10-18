<?php

class T3Lead_PaydayMonthlyEmails {
    /**
    * Добавление в систему нового email
    * 
    * @param mixed $email
    * @param string $datetime
    */
    static public function addEmail($id, $email, $home_phone, $datetime = null){
        if(is_null($datetime)) $datetime = date("Y-m-d H:i:s");
        
        try{
            T3Db::api()->insert("leads_payday_emails", array(
                'id'       => $id,
                'email'    => $email,
                'phone'    => $home_phone,
                'datetime' => $datetime,
            ));  
        }
        catch(Exception $e){
            
        } 
    } 
    
    /**
    * Удаление записей, старше 1 месяца
    * 
    */
    static public function deleteOld(){
        T3Db::api()->delete("leads_payday_emails", "`datetime` < '" . date("Y-m-d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))) . "'");
    } 
    
    
    static public function get($email){
        $startMicrotime = microtime(1);
               
        $result = array(
            'email' => '',
            'leadsCount' => '',
            'leads' => array(), 
        );
        
        $email = trim($email);
        $filter = new AZend_Filter_EmailRepair();
        $email = $filter->filter($email);
        
        $result['email'] = $email;
        
        $validator = new AZend_Validate_EmailAddress();
        if($validator->isValid($email)){
            $result['leads'] = T3Db::api()->fetchCol("select `datetime` from leads_payday_emails where email=?", $email); 
            $result['leadsCount'] = count($result['leads']);  
        }
        else {
            $result['error'] = array_values($validator->getMessages());    
        } 
        
        
        
        T3Db::api()->insert("leads_payday_emails_log", array(
            'datetime'      => date("Y-m-d H:i:s"),
            'email'         => $email,
            'resultCount'   => count($result['leads']),
            'runTime'       => microtime(1) - $startMicrotime,
        ));  
        
        return $result; 
    } 
    
    static public function getXML($email){
        $startMicrotime = microtime(1);

        /**
        * @var DOMDocument
        */
        $xml = new DOMDocument("1.0", "utf-8");

        $xmlResult = $xml->createElement("result");
        $xml->appendChild($xmlResult);


        $email = trim($email);
        $filter = new AZend_Filter_EmailRepair();
        $email = $filter->filter($email);

        $result->email = $email;

        $xmlResultEmail = $xml->createElement("email", $email);
        $xmlResult->appendChild($xmlResultEmail);


        $validator = new AZend_Validate_EmailAddress();
        if($validator->isValid($email)){
            /*
            $result['leads'] = T3Db::api()->fetchCol("select `datetime` from leads_payday_emails where email=?", $email);
            $result['leadsCount'] = count($result['leads']);
            */
            $ids = T3Db::api()->fetchCol("select id from leads_payday_emails where email=?", $email);
            $leads = array();
            if(count($ids)){
                $leads = T3Db::api()->fetchAll("select `datetime`, ttl from leads_data where id in (" . implode(",", $ids) . ")");
            }


            $xmlResultCount = $xml->createElement("count", count($leads));
            $xmlResult->appendChild($xmlResultCount);

            $xmlResultLeads = $xml->createElement("leads");
            $xmlResult->appendChild($xmlResultLeads);

            $leadsCount = count($leads);

            if(count($leads)){
                foreach($leads as $lead){
                    $xmlResultLeadsLead = $xml->createElement("leads");
                    $xmlResultLeads->appendChild($xmlResultLeadsLead);

                    if($lead['ttl'] <= 0)           $g = 0;
                    else if($lead['ttl'] <= 5)      $g = 1;
                    else if($lead['ttl'] <= 15)     $g = 2;
                    else if($lead['ttl'] <= 30)     $g = 3;
                    else if($lead['ttl'] <= 50)     $g = 4;
                    else if($lead['ttl'] <= 80)     $g = 5;
                    else                            $g = 6;

                    $xmlResultLeadsLead->appendChild($xml->createElement("date", $lead['datetime']));
                    $xmlResultLeadsLead->appendChild($xml->createElement("grade", $g));
                }
            }
        }
        else {
            $xmlResultCount = $xml->createElement("count", 0);
            $xmlResult->appendChild($xmlResultCount);

            $xmlResultLeads = $xml->createElement("leads");
            $xmlResult->appendChild($xmlResultLeads);

            $leadsCount = 0;

            $errors = $validator->getMessages();
            if(count($errors)){
                $xmlResultErrors = $xml->createElement("errors");
                $xmlResult->appendChild($xmlResultErrors);
                foreach($errors as $error){
                    if(is_string($error)){
                        $xmlResultErrors->appendChild($xml->createElement("error", $error));
                    }
                }
            }
        }

        T3Db::api()->insert("leads_payday_emails_log", array(
            'datetime'      => date("Y-m-d H:i:s"),
            'email'         => $email,
            'resultCount'   => $leadsCount,
            'runTime'       => microtime(1) - $startMicrotime,
            'ip'            => ifset($_SERVER['REMOTE_ADDR']),
        ));

        return $xml->saveXML();
    }

    static public function getPhonesXML($phone){
        $startMicrotime = microtime(1);

        /**
        * @var DOMDocument
        */
        $xml = new DOMDocument("1.0", "utf-8");

        $xmlResult = $xml->createElement("result");
        $xml->appendChild($xmlResult);


        $phone = trim($phone);
        $filter = new Zend_Filter_Digits();
        $phone = $filter->filter($phone);

        $result->phone = $phone;

        $xmlResultPhone = $xml->createElement("phone", $phone);
        $xmlResult->appendChild($xmlResultPhone);


        if(strlen($phone) == 10){
            /*
            $result['leads'] = T3Db::api()->fetchCol("select `datetime` from leads_payday_emails where email=?", $email);
            $result['leadsCount'] = count($result['leads']);
            */
            $ids = T3Db::api()->fetchCol("select id from leads_payday_emails where phone=?", $phone);
            $leads = array();
            if(count($ids)){
                $leads = T3Db::api()->fetchAll("select `datetime`, ttl from leads_data where id in (" . implode(",", $ids) . ")");
            }


            $xmlResultCount = $xml->createElement("count", count($leads));
            $xmlResult->appendChild($xmlResultCount);

            $xmlResultLeads = $xml->createElement("leads");
            $xmlResult->appendChild($xmlResultLeads);

            $leadsCount = count($leads);

            if(count($leads)){
                foreach($leads as $lead){
                    $xmlResultLeadsLead = $xml->createElement("leads");
                    $xmlResultLeads->appendChild($xmlResultLeadsLead);

                    if($lead['ttl'] <= 0)           $g = 0;
                    else if($lead['ttl'] <= 5)      $g = 1;
                    else if($lead['ttl'] <= 15)     $g = 2;
                    else if($lead['ttl'] <= 30)     $g = 3;
                    else if($lead['ttl'] <= 50)     $g = 4;
                    else if($lead['ttl'] <= 80)     $g = 5;
                    else                            $g = 6;

                    $xmlResultLeadsLead->appendChild($xml->createElement("date", $lead['datetime']));
                    $xmlResultLeadsLead->appendChild($xml->createElement("grade", $g));
                }
            }
        }
        else {
            $xmlResultCount = $xml->createElement("count", 0);
            $xmlResult->appendChild($xmlResultCount);

            $xmlResultLeads = $xml->createElement("leads");
            $xmlResult->appendChild($xmlResultLeads);

            $leadsCount = 0;

            $errors = $validator->getMessages();
            if(count($errors)){
                $xmlResultErrors = $xml->createElement("errors");
                $xmlResult->appendChild($xmlResultErrors);
                foreach($errors as $error){
                    if(is_string($error)){
                        $xmlResultErrors->appendChild($xml->createElement("error", $error));
                    }
                }
            }
        }

        T3Db::api()->insert("leads_payday_phones_log", array(
            'datetime'      => date("Y-m-d H:i:s"),
            'phone'         => $phone,
            'resultCount'   => $leadsCount,
            'runTime'       => microtime(1) - $startMicrotime,
            'ip'            => ifset($_SERVER['REMOTE_ADDR']),
        ));

        return $xml->saveXML();
    }
}