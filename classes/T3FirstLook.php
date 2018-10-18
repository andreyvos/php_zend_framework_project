<?php
class T3FirstLook extends DbSerializable {
    
    public static function check($leadID) {
        $lead = new T3LeadBody_PaydayLoan();
        $lead->id = $leadID;
        $lead->fromDatabase($lead->id);
        
        $xml = new SimpleXMLElement("<test_lender_decision></test_lender_decision>");
        $xml->username                 = 'davidgasparian';
        $xml->password                 = 'asd86YT66iou';
        $xml->account_id               = '403';
        $xml->group_id                 = '251';
        $xml->location_id              = '511';
        $xml->decision_type            = 'SOLD';
        $xml->tradeline_type           = 'C1';
        $xml->social_security_number   =$lead->ssn;
        
        $request = $xml->asXML();
        
        $sock=fsockopen('ssl://secure.clarityservices.com',443,$errno,$errstr,5);
        if ($sock){
            $data = "POST /lender_decisions/create_from_xml_test HTTP/1.0\r\n";
            $data.= "Host: secure.clarityservices.com\r\n";
            $data.= "Referer: t3leads.com\r\n";
            $data.= "Content-type: text/xml\r\n";
            $data.= "Content-Length: " . strlen($request) . "\r\n";
            $data.= "Accept: */*" . "\r\n";
            $data.= "\r\n";
            $data.= "$request\r\n";
            $data.= "\r\n";
            fwrite($sock, $data);
            $return="";
            while (!feof($sock)) {
                $return.=  @fgets($sock, 1024);
            }


            for($i=5;$i<strlen($return);$i++)
            {
                if(substr($return,$i-3,4)=="\r\n\r\n")
                {
                    $vis=true;
                    $contents =  substr($return,$i+1);
                    $i = strlen($return);
                }
            }
            echo $data;
            echo $return;
            fclose($sock);
        }      
    }
    
}
?>
