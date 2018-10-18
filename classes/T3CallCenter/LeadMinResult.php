<?php

class T3CallCenter_LeadMinResult{
    /**
    * no, verification, accept, reject - статус проверки лидав call центре
    *   no      - не проверялся
    *   now     - сейчас проверяется
    *   accept  - хороший
    *   reject  - плохой
    * @var string
    */
    public $status;
    
    /**
    * Дата когда лид был отправлен а call center. Только для status = verification, accept, reject 
    *  
    * @var string
    */
    public $addDate;
    
    /**
    * Дата когда лид был возвращен из Call центра (когда он был проверен) Только для status = accept, reject
    *   
    * @var string
    */
    public $runDate;
    
    /**
    * комментарий из call центра (Для status = reject)
    * 
    * @var string
    */
    public $reason;
    
    
    public function isStatusNo(){
        return ($this->status == 'no');
    } 
    
    public function isStatusNow(){
        return ($this->status == 'now');
    } 
    
    public function isStatusAccent(){
        return ($this->status == 'accept');
    } 
    
    public function isStatusReject(){
        return ($this->status == 'reject');
    }  
}