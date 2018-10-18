<?php

class T3Tracking_CashNow {
    public function ping($type, $num){
        try{
            T3Db::api()->insert("cashnow_tracking", array(
                'send_datetime' => date("Y-m-d H:i:s"),
                'lead'          => $num,
                'status'        => $type,   
            ));
        }
        catch(Exception $e){}
        
        if(!T3TestCluster::isTestMode()){
            $fp = fsockopen("api.cashadvance.com", 80, $errno, $errstr, 5);
            if ($fp) {
                $out = "GET /leadstatus.php?leadid={$num}.26046&status={$type} HTTP/1.1\r\n";
                $out .= "Host: api.cashadvance.com\r\n\r\n";
                fwrite($fp, $out);
                fclose($fp);
            }    
        }
    }
}