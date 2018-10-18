<?php

/*
1. Проверка лидов



2. Проверка Ретурнов (Возвратов)
*/

abstract class T3CallCenter_Abstract extends DbSerializable {
    public $id;       
    
    /**
    * ID лида, который проверяется
    * 
    * @var mixed
    */
    public $leadID;       
    
    public $createDate;        
    
    /**
    * Дата с которой с которой нужно начинать проверять запись.
    * Используется если до человека не дозвонились, что бы перезвонить через какое то время.
    * 
    * @var mixed
    */
    public $startDate;
    
    /**
    * Заполняется кроном, на основе поля $startDate, если Время подошло ставится 1.
    * Показатель того что клиента можно прозванивать, сделанно через отдельную переменную, потмучто так быстрее работать в mysql
    *        
    * @var mixed
    */
    public $active = '1';
    
    /**
    * ID Пользователя который на данный момент забронировал клиента
    * 
    * @var mixed
    */
    public $userID = '0'; 
    
    /**
    * Текущий статус  'wait','accept','reject'
    * wait - еще не проверен
    * accept - положительный ответ - подтверждение того что клиент хочет совершить (или совершил действие). 
    * reject - человек не совершил действие
    *      
    * @var mixed
    */
    public $status = 'wait';
    
    /**
    * Если человек не взял трубку, то этот параметр увеличивается на 1.
    *        
    * @var mixed
    */
    public $number_busy_count = '0';  
    
    /**
    * Код ответа агента
    *   
    * @var mixed
    */
    public $comment_code; 
    
    /**
    * Текст ответа агента (если введено что то не стандартное то может быть без кода)
    *        
    * @var mixed
    */
    public $comment_text; 
    
    /**
    * Коментариий которые видны тольок в админке - заметки для агентов проводящих верификацию
    *     
    * @var mixed
    */
    public $comment_for_agent;
    
    /**
    * Предположительная timezone клиента, для определения его времени. что бы не позвонить кому то в 5 утра
    * 
    * @var mixed
    */
    public $timezone_code = 'pst';
    
    /**
    * Лог работы с записью
    * 
    * @var mixed
    */
    public $history_work = array();
    
    
    public $lead_product;
    
    /**
    * Все контактные телефонные номера и желательное вермя для звонка.
    */
    public $home_phone; 
    public $work_phone; 
    public $work_phone_ext; 
    public $best_time_to_call; 
    
    
    /////////////////////////
    
    public $leadObject; 
    
    static public function createMain($className, $lead, $insert = false){
        if(is_numeric($lead)){
            $leadID = $lead;
            $lead = new T3Lead();
            $lead->fromDatabase($leadID);    
        }
        if(!(is_object($lead) && $lead instanceof T3Lead && $lead->id)) return false;    
        
        /** @var T3CallCenter_Abstract */
        $object = new $className();
        if(!($object instanceof T3CallCenter_Abstract)) return false;    
        
        $object->leadObject         = $lead;
        
        $object->lead_product       = $lead->product;
        
        $object->leadID             = $lead->id;
        $object->createDate         = date("Y:m:d H:i:s");
        $object->startDate          = date("Y:m:d H:i:s");
        $object->timezone_code      = AZend_Geo::getTimeZone_FromState($lead->getBody()->getParam('state'));
        
        $object->home_phone         = ifset($lead->getBody()->getParam('home_phone'));
        $object->work_phone         = ifset($lead->getBody()->getParam('work_phone'));
        $object->work_phone_ext     = ifset($lead->getBody()->getParam('work_phone_ext'));
        $object->best_time_to_call  = ifset($lead->getBody()->getParam('best_time_to_call'));
        
        if($insert)$object->insertIntoDatabase();
        
        return $object;
    }
    
    static public function getRejectTexts(){
        return array();    
    }
     
}