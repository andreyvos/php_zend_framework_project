<?php

class T3Sora_Types extends AP_UI_Decorator_Status {
    const TYPE_UNKNOWN      = 0; // not subaccount! - unknown

    const TYPE_TNX          = 1; // LinkForFormsThankYouPage
    const TYPE_DUP          = 2; // LinkForRepeatedClient
    const TYPE_EMAIL        = 3; // emailAllOffers
    const TYPE_SERVER_POST  = 4; // LinkForServerPostThankYouPage
    const TYPE_BAD_COUNTRY  = 5; // BadCountry
    const TYPE_POPUP_OFFER  = 6; // FeedClosePopup
    const TYPE_EMAIL_PAYDAY = 7; // emailUsPaydayOffers

    static public function create($value = null){
        return new self($value);
    }


    static protected $cache_types = null;

    static public function getTypesArray(){
        if(is_null(self::$cache_types)){
            self::$cache_types = array(0 => 'unknown') + T3Db::api()->fetchPairs(
                "SELECT `id`, `name` FROM `sora_subaccounts`"
            );
        }
        return self::$cache_types;
    }

    protected function init(){
        $list = self::getTypesArray();

        if(count($list)){
            foreach($list as $id => $name){
                $this->addStatus_White($id, $name);
            }
        }
    }
}