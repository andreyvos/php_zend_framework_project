<?php

class T3Sora_Statuses extends AP_UI_Decorator_Status {
    const STATUS_UNKNOWN        = 100;

    const STATUS_PROCESS        = 0;
    const STATUS_OK             = 1;
    const STATUS_REJECT         = 5;

    const STATUS_ERROR          = 2;
    const STATUS_ERROR_SEND     = 3;
    const STATUS_ERROR_PARSE    = 4;

    static public function create($value = null){
        return new self($value);
    }

    protected function init(){
        $this->addStatus_Red(       self::STATUS_UNKNOWN,       'Unknown');

        $this->addStatus_RedWrite(  self::STATUS_ERROR,         'Error');
        $this->addStatus_RedWrite(  self::STATUS_ERROR_PARSE,   'Error Parse');
        $this->addStatus_RedWrite(  self::STATUS_ERROR_SEND,    'Error Send');
        $this->addStatus_Green(     self::STATUS_OK,            'Ok');
        $this->addStatus_Yellow(    self::STATUS_PROCESS,       'Process');
        $this->addStatus_White(     self::STATUS_REJECT,        'Reject');
    }
}