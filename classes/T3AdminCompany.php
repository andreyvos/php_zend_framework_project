<?php

class T3AdminCompany implements T3CompanyInterface {

    public $id = '3000';
    public $systemName = 't3leads';
    public $companyName = 'D and D Marketing';

    public function  __construct() {
        
    }
    
    public function getCompanyAgent(){
        return null;    
    }
    
    public function getSiteManagementLink(){
        return null;    
    }
    
    /**
    * Роль для пользователя из этой компании
    */
    static public function getConst_UserRole(){
        return 'admin';
            
    }
    
    public function getTotalAmount(){
        return 0;    
    }
    
    public function getPaidAmount(){
        return 0; 
    }

}

