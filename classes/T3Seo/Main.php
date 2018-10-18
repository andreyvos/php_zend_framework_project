<?php

class T3Seo_Main {
    
    static public function createUser($login, $password, $nickname, $email, $agentOptions = array()){
        $user = T3Users::createT3Worker($login, $password, $nickname, $email, 'seo');
        
        if($user){ 
            $seoMan = new T3Seo_User();
            $seoMan->setParams($agentOptions); 
            $seoMan->id = $user->id;
            $seoMan->insertIntoDatabase();
            
            return $seoMan;
        }
        else {
            return false;
        } 
    }    
}