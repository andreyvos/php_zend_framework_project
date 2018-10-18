<?php

class T3CallCenter2 {
    public $url = "http://call.t3leads.com/public/Interface.php";
   public function sendLeadToVerification($info,$templateHtml="",$login = "test",$password = "pass" )
   {
       $dataToSend = array();
       $dataToSend['login']    = $login;
       $dataToSend['password'] = $password;
       $dataToSend['scenario'] = "scenario";
       $dataToSend['template'] = $templateHtml;
       $dataToSend['data']     = var_export($info,true);
       $dataToSend['product']  = $info['product'];

       $fields_string ="";
       foreach($dataToSend as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
       rtrim($fields_string,'&');
       $ch = curl_init();
        //die(var_dump($fields_string));
	//set the url, number of POST vars, POST data
       curl_setopt($ch,CURLOPT_URL,$this->url);
       curl_setopt($ch,CURLOPT_POST,count($dataToSend));
       curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
       curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
       curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
       //execute post
       $result = curl_exec($ch);
       //close connection
       curl_close($ch);
       return $result;
   }
   
   public function getInfoFromLead(T3Lead $lead){
       $info = array(
                    'home_phone'=>$lead->getBodyFromDatabase()->home_phone,
                    'work_phone'=>$lead->getBodyFromDatabase()->work_phone,
                    'cell_phone'=>$lead->getBodyFromDatabase()->cell_phone,
                    'best_time_to_call'=>$lead->getBodyFromDatabase()->best_time_to_call,
	             'first_name'=>$lead->getBodyFromDatabase()->first_name,
	             'last_name'=>$lead->getBodyFromDatabase()->last_name,
	             'product'=>$lead->product
        	    );
       return $info;
   }


   public function getTemplate($lead){
       $template =
            "
                <div style='font-size: 16px;'>
                    <p><b style='color: red;'>{%first_name%} {%last_name%}</b> filled the Payday Loans Form.</p>

                    <p>Check if  <b style='color: red;'>{%first_name%} {%last_name%}</b> fills out a form and wanted to take the loan.</p>

                    <p>Call to the one of the numbers:</p>

                    <p>Home phone: <b style='color: red;'>{%home_phone%}</b></p>

                    <p>Work Phone: <b style='color: red;'>{%work_phone%}</b></p>

                    <p>Cell Phone: <b style='color: red;'>{%cell_phone%}</b></p>

                    <p>Preferably in the <b style='color: red;'>{%best_time_to_call%}</b></p>

                </div>
            ";
       return $template;
   }


}
?>
