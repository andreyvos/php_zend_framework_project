<?
/*
Результат работы может быть следующий:
1) Статус - Продан / Не продан / Сетевая ошибка / Ошибка

2) Статус Продан: Цена
3) Статус Продан: Редирект Линк

4) Не продан: 3 варианта. Комментарий - известный / не известный

5) Ошибка: Одна ошибка
*/


/**
* Результат работы может быть следующий:
* 1) Статус: 
*     Sold          -  продан
*     PriceConflict -  Баер ответил что лид продан, но лид был не продан по причине того что он указал цену меньшую чем должен был указать   
*     Error         -  Ошибка при продаже
*     Filtered      -  Отфильтрован
*     SendError     -  Ошибка при отправке 
*     Rejected      -  Лид не продан 
*     Duplicated    -  Лид не отправлялся потомучто баер его уже видел
*     Unknown       -  Другое (Когда все будет готово, такого статуса не будет) 
* 
* Параметры:
* 1) Цена
* 2) Редирект линк
* 3) Комментарий Reject  
* 4) Комментарий Error 
* 5) Комментарий Response  
*/


/**
* Лид продан
*/
define("PostResult_Buyer_Sold",'Sold');

/**
* Баер ответил что лид продан, но лид был не продан по причине того что он указал цену меньшую чем должен был указать
*/
define("PostResult_Buyer_PriceConflict",'PriceConflict');

/**
* Ошибка при продаже  
*/
define("PostResult_Buyer_Error",'Error');

/**
* Ошибка при продаже  
*/
define("PostResult_Buyer_ConfigError",'ConfigError');

/**
* Отфильтрован  
*/
define("PostResult_Buyer_Filtered",'Filtered');

/**
* Ошибка при отправке
*/
define("PostResult_Buyer_SendError",'SendError');

/**
* Ошибка при отправке
*/
define("PostResult_Buyer_AnalysisError",'AnalysisError');


/**
* Лид не продан 
*/
define("PostResult_Buyer_Rejected",'Rejected');

/**
* Лид не продан, потому что ранее был реджектан другим каналом, и реджект распространился на данноый канал 
*/
define("PostResult_Buyer_GlobalReject",'GlobalReject');


/**
* Лид не отправлялся потомучто баер его уже видел
*/
define("PostResult_Buyer_Duplicated",'Duplicated');

/**
* Другое 
*/
define("PostResult_Buyer_Unknown",'Unknown');

TableDescription::addTable('buyers_channels_post_results', array(
    'id',
    'buyerChannelID',
    'leadID',
    'startDate',
    
    'publisher',
    'pub_channel',
    'pub_subaccount',

    'secondsAll',

    'secondsGlobalRejectCheck',
    'secondsDuplicateCheck',
    'secondsFilteresCheck',

    'secondsLoadConfig',
    'secondsReadConfig',

    'secondsRunCollect',
    'secondsRunSend',
    'secondsRunAnalysis',

    'secondsPayment',

    'status',

    'price',
    'priceTTL',
    
    'priceRuleWM',
    'priceRuleTTL',
    'minPrice',

    'redirectLink',
    'rejectedComment',

    'errorType', 
    'errorDescription', 
    'errorSysDescription', 

    'response',

    'isDuplicated',
    'isFiltered',
    'isSend',
    'isSold',
    'isError',
    'isTest',
    'isTimeout',

    'sendLog',
));

class T3BuyerChannel_PostResult extends DbSerializable  {
    /**
    * ID записи
    * 
    * @var int
    */
    protected $id;
    
    /**
    * ID канала покупателя
    * 
    * @var int
    */
    public $buyerChannelID;
    
    public $buyerID;
    
    /**
    * ID лида
    * 
    * @var int
    */
    public $leadID;
    
    /**
    * put your comment there...
    * 
    * @var T3Lead
    */
    public $leadObject;
    
    
    
    /**********************************************************************************************************
    * Дата начала постинга
    * 
    * @var string date
    */
    public $startDate;
    
    
    public $publisher;
    public $pub_channel;
    public $pub_subaccount;
    
    
    /**********************************************************************************************************
    * Время затраценное на различные действия
    */
    
    public $secondsAll = 0;
    
    public $secondsGlobalRejectCheck = 0;
    public $secondsDuplicateCheck = 0; 
    public $secondsFilteresCheck = 0; 
    
    public $secondsLoadConfig = 0;
    public $secondsReadConfig = 0;
    
    public $secondsRunCollect = 0;
    public $secondsRunSend = 0;
    public $secondsRunAnalysis = 0;
    
    public $secondsPayment = 0; 
    
    /**********************************************************************************************************
    * Статус постинга 
    * 
    * @var string
    */
    public $status;
    
    /**********************************************************************************************************
    * Цены
    */
    
    /**
    * Сумма, которую надо отдать 3 лицам
    * 
    * @var float
    */
    public $priceExternal = 0;
    
    /**
    * Цена, которая платится вебмастеру
    * 
    * @var float
    */
    public $price; 
    
    /**
    * Цена, которуб дал баер
    * 
    * @var float
    */
    public $priceTTL; 
    
    
    //  означает доход вебмасетра и общая цена лида, если лид будет продан по minConstPrice
    public $priceRuleWM = 0;
    public $priceRuleTTL = 0;
    
    public $minPrice = 0;
    
    /**
    * Ссылка на редирект
    *           
    * @var mixed
    */
    public $redirectLink;
    
    /**
    * Комментарий, который баер вернул как описание: "Почему лид не был принят"
    *     
    * @var mixed
    */
    public $rejectedComment; 
    
    /**
    * Тип ошибки если она произошла
    * 
    * @var mixed
    */
    public $errorType;
    
    /**
    * Описание ошибки, которое можно показать менеджеру
    * 
    * @var mixed
    */
    public $errorDescription; 
    
    /**
    * Описание ошибки для програмиста
    *    
    * @var mixed
    */
    public $errorSysDescription;   
    
    /**
    * Какое то описание нашей системы (Например сюда запписывается коментарий когда был конфликт цен)
    * 
    * @var mixed
    */
    public $response;
    
    /**
    * Показатель был ли лид дупликат (текие лиды не пишутся в базу данных)
    * 
    * @var mixed
    */
    protected $isDuplicated = '0'; 
    
    /**
    * Показывает был ли лид отфилтрован
    * 
    * @var mixed
    */
    protected $isFiltered = false;
    
    /**
    * Показывает был ли лид Отправлен
    * 
    * @var mixed
    */
    protected $isSend = false;
    
    /**
    * Показывает был ли лид продан
    * 
    * @var mixed
    */
    protected $isSold = false;
    
    /**
    * Показывает что произошла ошибка
    * 
    * @var mixed
    */
    protected $isError = false;
    
    /**
    * Показывает это тестовый лид или нет
    * 
    * @var bool
    */
    protected $isTest = false;
    
    /**
    * Показывает был ли он таймаут
    * 
    * @var bool
    */
    protected $isTimeout = false;
    
    
    
    /**
    * Лог отправки
    * 
    * @var array
    */
    public $sendLog = array();
    
    
    /**********************************************************************************************************************************************************
    * Конструктор
    * 
    * @param T3BuyerChannel $buyerChannel
    * @return T3BuyerChannel_PostResult
    */
    public function  __construct(T3Lead $lead = null, T3BuyerChannel $buyerChannel = null) {
        if (!isset($this->className))
            $this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('buyers_channels_post_results');
        
        $this->status = PostResult_Buyer_Unknown;
        
        $this->isDuplicated = false;
        $this->isFiltered = false;
        $this->isSend = false;
        $this->isSold = false;
        
        if(!is_null($buyerChannel)){
            $this->buyerChannelID = $buyerChannel->id;
            $this->buyerID = $buyerChannel->buyer_id;
        }
        
        if(!is_null($lead)){
            $this->leadObject = $lead;
            $this->leadID = $lead->id;
        }
    }
    
    /**
    * Проверить была ли эта продажа преостановленна из за того что лид Duplicate
    * Используя дополнительный параметр можно изменить значение
    * 
    * @param bool $reValue
    */
    public function isDuplicated($reValue = null){
        if(!is_null($reValue))$this->isDuplicated = (bool)$reValue;
        return (string)(int)(bool)$this->isDuplicated;    
    }
    
    /**
    * Проверить была ли эта продажа преостановленна из за того что не подошли фильтры
    * Используя дополнительный параметр можно изменить значение
    * 
    * @param bool $reValue
    */
    public function isFiltered($reValue = null){
        if(!is_null($reValue))$this->isFiltered = (bool)$reValue; 
        return (string)(int)(bool)$this->isFiltered;    
    }
    
    /**
    * Проверить был ли этот лид доставлен до покупателя (покупатель его получил?)
    * Используя дополнительный параметр можно изменить значение
    * 
    * @param bool $reValue
    */
    public function isSend($reValue = null){
        if(!is_null($reValue))$this->isSend = (bool)$reValue; 
        return (string)(int)(bool)$this->isSend;    
    }
    
    /**
    * Проверить отправляется ли этот лид в режиме теста
    * Используя дополнительный параметр можно изменить значение  
    * 
    * @param bool $reValue
    */
    public function isTest($reValue = null){
        if(!is_null($reValue))$this->isTest = (bool)$reValue; 
        return (string)(int)(bool)$this->isTest;    
    }
    
    /**
    * Проверить бул ли этот лид продан
    * Используя дополнительный параметр можно изменить значение
    * 
    * @param bool $reValue
    */
    public function isSold($reValue = null){
        if(!is_null($reValue))$this->isSold = (bool)$reValue; 
        return (string)(int)(bool)$this->isSold;    
    }
    
    /**
    * Проверить бул ли этот лид продан
    * Используя дополнительный параметр можно изменить значение
    * 
    * @param bool $reValue
    */
    public function isError($reValue = null){
        if(!is_null($reValue))$this->isError = (bool)$reValue; 
        return (string)(int)(bool)$this->isError;    
    }
    
    /**
    * @param bool $reValue
    */
    public function isTimeout($reValue = null){
        if(!is_null($reValue))$this->isTimeout = (bool)$reValue; 
        return (string)(int)(bool)$this->isTimeout;    
    }
    
    public function setPriceConflict($message = null){
        $this->status = PostResult_Buyer_PriceConflict;
        $this->response = $message;
        $this->isError(true); 
    }

    
    
    public function setFiltered($message = null){
        $this->status = PostResult_Buyer_Filtered;
        $this->errorDescription = $message;
        $this->isFiltered(true);
    }
    
    

    public function setSold($price, $priceTTL, $redirectLink){
        $this->status = PostResult_Buyer_Sold;
        $this->price = $price; 
        $this->priceTTL = $priceTTL; 
        $this->redirectLink = $redirectLink;
        $this->isSold(true);
    }

    public function setRejected($message = null){
        $this->status = PostResult_Buyer_Rejected;
        $this->rejectedComment = $message;
    }
    
    public function setDuplicated(){
        $this->status = PostResult_Buyer_Duplicated;
        $this->isDuplicated(true);
    }
    
    /**
    * Произошла Другая Ошибка
    * 
    * @param T3PostingFile_Result_Abstract|string $error
    * @param string $description
    * @param string $sys_description
    */
    public function setError($error,$description = null,$sys_description = null){
        $this->setErrorAdd(PostResult_Buyer_Error, $error, $description, $sys_description);
        $this->isError(true);
    }
    
    /**
    * Произошла Ошибка при отправке данных
    * 
    * @param T3PostingFile_Result_Abstract|string $error
    * @param string $description
    * @param string $sys_description
    */
    public function setErrorSend($error,$description = null,$sys_description = null){
        $this->setErrorAdd(PostResult_Buyer_SendError, $error, $description, $sys_description);
        $this->isError(true);
    }
    
    /**
    * Произошла Ошибка при анализе данных
    * 
    * @param T3PostingFile_Result_Abstract|string $error
    * @param string $description
    * @param string $sys_description
    */
    public function setErrorAnalysis($error,$description = null,$sys_description = null){
        $this->setErrorAdd(PostResult_Buyer_AnalysisError, $error, $description, $sys_description);
        $this->isError(true);
    }
    
    /**
    * Произошла Ошибка при анализе конфига
    * 
    * @param T3PostingFile_Result_Abstract|string $error
    * @param string $description
    * @param string $sys_description
    */
    public function setErrorConfig($error,$description = null,$sys_description = null){
        $this->setErrorAdd(PostResult_Buyer_ConfigError, $error, $description, $sys_description);
        $this->isError(true);
    }
    
    /**
    * Добавление ошибки
    * 
    * @param string $status
    * @param T3PostingFile_Result_Abstract|string $error
    * @param string $description
    * @param string $sys_description
    */
    protected function setErrorAdd($status, $error, $description = null, $sys_description = null){

        if(is_object($error) && is_subclass_of($error, 'T3PostingFile_Result_Abstract')){
            
            if(is_null($description))$description = $error->error; 
            if(is_null($sys_description))$sys_description = $error->sys_error;
            
            $error = $error->status;     
        }
        
        $this->status               =   $status;
        $this->errorType            =   $error;
        $this->errorSysDescription  =   $sys_description;
        $this->errorDescription     =   $description;
    }
    
    /**
    * Глобальный Reject. Этот лид был Rejeced другим постингом из группы в которую входит данный постинг
    * 
    * @param array $resultArray
    */
    public function setGlobalReject($resultArray){
        $this->status               =   PostResult_Buyer_GlobalReject;
        //$this->rejectedComment      =   "Global Reject: Buyer ID: {$resultArray['buyerID']}, Datetime: {$resultArray['datetime']}";  
        $this->rejectedComment      =   "Global Reject";  
        $this->isFiltered(true);
    }
    
    /**
    * Сохранение в базу данных
    */
    public function save(){
        /*
        if(isset($this->id)){
            $this->saveToDatabase();
        } 
        else {
            $this->insertIntoDatabase();
        } 
        */  
    }
    
    public function toArray($tables = null){
        $tempSendLog = $this->sendLog;
        $this->sendLog = varExportSafe($this->sendLog);
        
        $this->isDuplicated     = (int)$this->isDuplicated;
        $this->isFiltered       = (int)$this->isFiltered;
        $this->isSend           = (int)$this->isSend;
        $this->isSold           = (int)$this->isSold; 
        $this->isTest           = (int)$this->isTest; 
        $this->isError          = (int)$this->isError;
        $this->isTimeout          = (int)$this->isTimeout; 
        
        $return = parent::toArray($tables);
        
        $this->sendLog = $tempSendLog;
        
        return $return;
    }

    public function fromArray(&$array){
        parent::fromArray($array);
        $this->sendLog = varImport($this->sendLog);
    }
    
    /**
    * Получить ID ответа
    */
    public function getID(){
        Return $this->id;    
    }
    
    public function addLog(){
        // Добавление записи в систему
        if($this->leadObject){
            T3BuyerChannel_Logs::add($this->leadObject, $this);
        }    
    }
}