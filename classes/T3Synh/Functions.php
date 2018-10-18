<?php

class T3Synh_Functions {
    static public function getAgentID($login){
        $array = array(
            'Vlad'          => '1018365',
            'CarynJ'        => '1000035',
            'MorganGethers' => '1019879',
        );
        
        if(isset($array[$login]))  return $array[$login];
        else return '0';
    }  
    
    
    static public function getPostMethod($old){
        $array = array(
            'feed'     => 'js_form',
            'posting'  => 'post_channel',

        );
        
        if(isset($array[$old]))  return $array[$old];
        else return 'post_channel'; 
    }  
}