<?php

class T3Widgets_Notifications extends T3Widgets_Abstract {
    /**
    * @var T3User
    */
    protected $user;
    protected $company;  
    
    public $userNotifatations;
    public $companyNotifatations;
    
    public function __construct(){
        $this->user = T3Users::getInstance()->getCurrentUser(); 
        
        if(!$this->user){
            $this->show = false;
        }
        else {
            $this->company = $this->user->getUserCompany();
            
            $this->userNotifatations = AZend_Notifications::getNotificationsToUser($this->user->id);
            $this->companyNotifatations = AZend_Notifications::getNotificationsToCompany($this->company->id);
            
            if(!count($this->userNotifatations) && !count($this->companyNotifatations)){
                $this->show = false;    
            }
        }   
    }      
}