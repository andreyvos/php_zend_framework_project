<?php

class T3Widgets_AccountRepresentative extends T3Widgets_Abstract {
    protected $agent;
    
    public function setAgent(T3UserWebmasterAgent $agent){
        $this->agent = $agent;    
    }
    
    public function __construct(){
        $this->show = false; 
        $company = T3Users::getInstance()->getCurrentUser()->getUserCompany(); 
        
        if($company){
            $agent = $company->getCompanyAgent();
            
            if($agent){
                $this->agent = $agent;
                $this->show = true; 
            }   
        }  
    }       
}