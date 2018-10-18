<?php

class T3Channel_Errors {
    static public function addError(T3Lead $lead, $errorImportance, $errorValue, $errorVar, $errorType, $errorDescription, $errorsCount){
        T3Db::api()->insert("channels_errors", array(
            'create_datetime'       => new Zend_Db_Expr("NOW()"),
            'leadid'                => $lead->id,
            'webmaster'             => $lead->affid,
            'subaccount'            => $lead->subacc,
            'subaccount_str'        => $lead->subacc_str,
            'channel_type'          => $lead->get_method,
            'channel_id'            => $lead->channel_id,
            'product'               => $lead->product,
            'value'                 => $errorValue, 
            'var'                   => $errorVar, 
            'importance'            => $errorImportance,
            'error_type'            => $errorType,
            'error_description'     => $errorDescription,
            'lead_errors_count'     => $errorsCount,
        ));
    }    
}