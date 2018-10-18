<?php

TableDescription::addTable('call_center_new_leads', array(
    'id',        
    'leadID',         
    'createDate',       
    'startDate',     
    'active',    
    'userID',         
    'status',           
    'number_busy_count',     
    'comment_code',     
    'comment_text',       
    'comment_for_agent',
    'timezone_code',      
    'history_work',  
    'lead_product',    
    'home_phone',        
    'work_phone',   
    'work_phone_ext',      
    'cell_phone',      
    'best_time_to_call',
));

class T3CallCenter_NewLead extends T3CallCenter_Abstract {
    public function __construct() {
        parent::__construct();
        $this->tables = array('call_center_new_leads');
    }
    
    static public function createLeadVefification($lead){
        /** @var T3CallCenter_NewLead */
        return parent::createMain('T3CallCenter_NewLead', $lead, true);
    }
    
    static public function getRejectTexts(){
        return array(
            'Bad_Phone'             => 'Bad Phone',
            'Never_Applied'         => 'Never Applied',
            'Not_Interested'        => 'Not Interested',
            'Disconnected_Number'   => 'Disconnected Number',
            'Could_not_reach'       => 'Could Not Reach',
        );
    }        
}