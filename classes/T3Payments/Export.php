<?php

class T3Payments_Export {
    function render($system, $data, $params = array()){
        $renderTypes = array(
            'Paypal'      =>  T3Payments::SYS_PAYPAL,
            'Webmoney'    =>  T3Payments::SYS_WEBMONEY,
            'Epassporte'  =>  T3Payments::SYS_EPASS,
            'Wire'        =>  T3Payments::SYS_WIRE,
            'Check'       =>  T3Payments::SYS_CHECK,
            'ach_3_business_days'       =>  T3Payments::SYS_ACH_3_BUSINESS_DAYS, 
            'ach_next_business_day'       =>  T3Payments::SYS_ACH_NEXT_BUSINESS_DAY, 
            'ach_same_day'       =>  T3Payments::SYS_ACH_SAME_DAY, 
        );
        
        $renderFile = '';
        foreach($renderTypes as $renderType => $systems){
            if(
                (is_string($systems) && $systems == $system) ||
                (is_array($systems) && array_search($system, $systems) !== false)
            ){
                $renderFile = $system;
                break;
            }        
        }
        
        if($renderFile && is_array($data) && count($data)){
            $view = new Zend_View();
            $view->setScriptPath(dirname(__FILE__) . DS . "Export");
            
            $view->data = $data;
            $view->type = $system;
            $view->params = $params;
            
            return $view->render($renderFile . ".phtml");    
        }
        
        return null;
    }
}