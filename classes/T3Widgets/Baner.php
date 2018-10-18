<?php

class T3Widgets_Baner extends T3Widgets_Abstract {
    /**
    * @var T3User
    */
    protected $user;  
    
    protected $userRole;
    
    protected $companyID;
    protected $companyStatus;
    protected $companyBalance;
    protected $companyName;
    
    public function __construct(){
        
        $this->user = T3Users::getInstance()->getCurrentUser(); 
//        varExport($this->user->ipad);die;
        if(!$this->user){
            $this->show = false;
        }
        else {
            $company = $this->user->getUserCompany();
            
            if($company instanceof T3AdminCompany){
                $this->companyID = $this->user->company_id;
                $this->companyName = "T3Leads";
                $this->userRole = $this->user->role;   
                      
            }
            else {
                $currency = new Zend_Currency('en_US');
                
                $this->companyID = $company->id;
                $this->companyStatus = T3UserCompany::getStatusTitleHtml($company->status);
                $this->companyBalance = $currency->toCurrency($company->balance);       
            }
        }   
    }    
}