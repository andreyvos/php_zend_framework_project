<?php

class T3DelayedLeadSend
{
    public static function addLead($leadID,$buyerChannelID,$waitSeconds)
    {
        $currentTime = time();
        T3Db::api()->insert('delayed_leads', array(
                                                    'leadID'=>$leadID,
                                                    'buyerChannelID'=>$buyerChannelID,
                                                    'waitSeconds'=>$waitSeconds,
                                                    'datetimeAdded'=>$currentTime,
                                                    'datetimeSend'=>$currentTime+$waitSeconds
        ));
    }
    public static function sendLeads()
    {
    
        $leadsCountToSend =  T3Db::api()->select()->from('delayed_leads',"count(*)")->where('datetimeSend<=?',time())->query()->fetchColumn();
        if($leadsCountToSend==0)
            return 0;
        if($leadsCountToSend>200)
            $leadsCountToSend =200;
            
        $leadsToSend =  T3Db::api()->select()->from('delayed_leads')->where('datetimeSend<=?',time())->limit($leadsCountToSend)->query()->fetchAll();
        
        $removeIds = array();
        $oneTimeCount = 5; 
        $k=0;
        $j=0;
        //varDump((count($leadsToSend)%$oneTimeCount),false);
       for($j=0;$j<count($leadsToSend)-(count($leadsToSend)%$oneTimeCount);$j+=$oneTimeCount )
       {
          $oneTimeData = array();
          $f=0;
          for($k=$j;$k<$j+$oneTimeCount;$k++) 
          {
          
              $oneLead =  $leadsToSend[$k];
              $id =  $oneLead['id'];
              $leadID = $oneLead['leadID'];
              $buyerChannelID = $oneLead['buyerChannelID'];
              $oneTimeData["leadsData[{$f}]"] = array("leadID"=>$leadID,"buyerChannelID"=>$buyerChannelID);
              
              $f++;
              $removeIds[] = $id;
          }
          //varDump($oneTimeData,false);
          if(count($oneTimeData))
            curl_request_async("http://t3leads.com/T3System/scripts/send_one_delayed_lead.php",$oneTimeData,"GET");  



          
       }
       
       $f=0;
       $oneTimeData = array();
       for($i=$k;$i<count($leadsToSend);$i++)
       {
              $oneLead =  $leadsToSend[$i];
              $id =  $oneLead['id'];
              $leadID = $oneLead['leadID'];
              $buyerChannelID = $oneLead['buyerChannelID'];
              $oneTimeData["leadsData[{$f}]"] = array("leadID"=>$leadID,"buyerChannelID"=>$buyerChannelID);

              $f++;
              $removeIds[] = $id;
       }
       if(count($oneTimeData))
        curl_request_async("http://t3leads.com/T3System/scripts/send_one_delayed_lead.php",$oneTimeData,"GET");  
      // varDump(array($j,$k,$i),false);
       if(count($removeIds))
       {
           $removeIdsString = implode(',', $removeIds) ;
           
           T3Db::api()->delete('delayed_leads', 'id in ('.$removeIdsString.')');
       }
       return count($removeIds);
       
    }
    public static  function getDelay($currentDate,$startDate)
    {
        $datetimeCurrent = new Zend_Date($currentDate);
        $datetimeStart = new Zend_Date($startDate);
        $datetimeCurrent->sub($datetimeStart);
        return  $datetimeCurrent->toValue(Zend_Date::DAY)*60*30;
    }
}

  function curl_request_async($url, $params, $type='POST')
  {
      foreach ($params as $key => &$val) {
        if (is_array($val)) $val = implode(',', $val);
        $post_params[] = $key.'='.urlencode($val);
      }
      $post_string = implode('&', $post_params);

      $parts=parse_url($url);

      $fp = fsockopen($parts['host'],
          isset($parts['port'])?$parts['port']:80,
          $errno, $errstr, 30);

      // Data goes in the path for a GET request
      if('GET' == $type) $parts['path'] .= '?'.$post_string;

      $out = "$type ".$parts['path']." HTTP/1.1\r\n";
      $out.= "Host: ".$parts['host']."\r\n";
      $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
      $out.= "Content-Length: ".strlen($post_string)."\r\n";
      $out.= "Connection: Close\r\n\r\n";
      // Data goes in the request body for a POST request
      if ('POST' == $type && isset($post_string)) $out.= $post_string;
      fwrite($fp, $out);
      fclose($fp);
  }
