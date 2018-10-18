<?php

class T3Validator_BuyerChannelStatus extends T3Validator_InArray {

    public function  initialize() {
        parent::defInit(array(
            'just_created',
            'paused',
            'active'
        ));
    }
    
    public function defInit(){}  
}