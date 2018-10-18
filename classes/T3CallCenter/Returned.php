<?php

TableDescription::addTable('call_center_returned', array(
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
    
    'returnedID',
    'buyersLeadsSellingID'
));

class T3CallCenter_Returned extends T3CallCenter_Abstract {
    public function __construct() {
        parent::__construct();
        $this->tables = array('call_center_returned');
    }
    
    static public function createLeadVefification($lead){
        $object = parent::createMain('T3CallCenter_Returned', $lead);
        
        if($object){
            $object->insertIntoDatabase();    
        }
        
        return $object;
    }        
}