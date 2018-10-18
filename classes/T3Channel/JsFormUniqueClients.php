<?php
 
class T3Channel_JsFormUniqueClients {
    static public function createClickVersion2(
        $webmasterID,
        $product,
        $channelID,
        $template,
        $clientIP,
        $channel_type = 'js_form',
        $date = null,
        $ref_url = "",
        $subacc = "",
        $isMobile=0   //Added 
    ){
        if(is_null($date)) $date = date("Y-m-d H:i:s");
        
        $click = new T3Channel_JsFormUniqueClient();
        
        $click->webmaster       = $webmasterID;
        $click->agent           = T3WebmasterCompanys::getAgentID($webmasterID);
        $click->product         = $product;
        $click->channel_type    = $channel_type;
        $click->channel_id      = $channelID;
        $click->date            = $date;
        $click->template        = $template;
        $click->clientIPInt     = myHttp::get_ip_num($clientIP);
        $click->clientIP        = $clientIP;
        $click->version         = '2';
        $click->ref_url         = $ref_url;
        $click->subaccount      = $subacc==""?"0":$subacc;
        $click->isMobile        = $isMobile; //Added
        
//        $click->insertIntoDatabase(); 
        
        T3Report_Summary::addNewClick($click);
    } 
}