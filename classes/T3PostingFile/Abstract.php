<?php

abstract class T3PostingFile_Abstract {
    /**
    * Документации/Документация на основе которых построенн конечный файл
    * 
    * @var string|array
    */
    protected $documentation;
    
    /**
    * Текущий лид
    * 
    * @var T3Lead
    */
    protected $lead;
    
    /**
    * Текущий канал баера
    * 
    * @var T3BuyerChannel
    */
    protected $buyerChannel;
    
    /**
    * Тело текущего лида
    * 
    * @var mix Classes T3LeadBody_*
    */
    protected $body;
    
    /**
    * Массив данных конфигурации
    * 
    * @var array
    */
    protected $conf;
    
    /**
    * Переменные которые необходимы скрипту для работы, определяются в конструкторе конечного класса, 
    * Массив необходимых переменных можно получить используя метод: this->getConfigValues
    * Массив предназначен для использоваия а Zend_Form
    * 
    * @var array
    */
    protected $configValues = array(); // массив переменных необходимых для конечного файла
    
    /**
    * Результат работы скртипа
    * 
    * @var T3PostingFile_Result_Abstract
    */
    protected $result;
    
    
    protected function checkConfigValues(){
        /**
        * Проверить були ли переданны в конфиг все необходимые переменные
        * 
        */
        $this->conf;                // Массив переменных переданных в конфиге
        $this->configValues;        // Описание пересенных, которые используются в скрипте
        
        foreach($this->configValues as $valueName => $options){
            if(!isset($this->conf[$valueName])){
                if(isset($options['required']) && $options['required'] == true){
                    # отсутсвует обязательная переменная
                    return array(
                        "Plaese settings config values from {$this->buyerChannel->id} Buyer channel.",
                        "Not Value: {$valueName} from {$this->buyerChannel->id} Buyer channel."
                    );
                }
                else {
                    # отсутсвует необязательная переменная
                    if(isset($options['default']))  $this->conf[$valueName] = $options['default'];
                    else                            $this->conf[$valueName] = null;
                }       
            }  
        }
        
        return true;     
    }
    
    /**
    * Получить массив необходимых переменных, которые требует скрипт конечного файла
    */
    public function getConfigValues(){
        return $this->configValues;    
    }
    
    /**
    * Получить массив документаций, на основе которых построен конечный файл
    */
    public function getDocumentations(){
        $return = array();
        
        if(is_string($this->documentation))$this->documentation = array($this->documentation);
        
        if(is_array($this->documentation) && count($this->documentation)){
            foreach($this->documentation as $doc){
                if(is_string($doc) && strlen($doc)){
                    $return[] = $doc;    
                }   
            }
        }
        
        return $return;    
    }
    
    /**
    * Проверка входящего конфига
    * Сохранение входящий переменных в класс
    * 
    * Эта функция переопределяется в более точных абстракциях data send и analysis файлов
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $byuerChannel
    * @param array $PostingConfigData
    */
    public function init(T3Lead $lead, T3BuyerChannel $byuerChannel){
        $this->buyerChannel = $byuerChannel;
        $this->conf =& $this->buyerChannel->getConfig()->PostingConfigData;
        
        $this->lead = $lead;
        $this->body = $lead->getBody();
        
        $resultConfig = $this->checkConfigValues();
        if($resultConfig !== true){
            $this->result->setErrorConfig($resultConfig[0],$resultConfig[1]);  
            return false; # в конфиге нет каких то переменных
        }
        
        return true;
    }
    
    /**
    * Отдает Secure Sub ID
    * Можно передать format с единственным указателем %s, в который будет вставляться текущий SubID
    * Например: getSecureSubID('T3Leads%s') = 'T3Leads86778123'
    * 
    * @param mixed $format
    * @return string
    */
    protected function getSecureSubID($format = null){
        if(is_null($format) || $format == ""){
            return T3SecureSubID::get_ssID($this->lead,$this->buyerChannel);
        }
        else {
            return sprintf($format,T3SecureSubID::get_ssID($this->lead,$this->buyerChannel));
        }        
    }
    
    /**
    * Секретный SubID продавца
    */
    protected function secureSubID(){
        return T3SecureSubID::get_ssID($this->lead, $this->buyerChannel);     
    }
    
    /**
    * Выход из постинга с указанием причины
    */
    protected function exitPosting($responce, $sys_responce = null){
        $this->result->setManualExit($responce, $sys_responce);    
    }
    
    /**
    * Выход из постинга с указанием причины
    */
    protected function exitPosting_PingReject($responce, $sys_responce = null){
        $this->result->setPingReject($responce, $sys_responce);    
    }
    
    protected function addHttpPostHeaderValue($var, $val){
        if(!isset($this->conf['send_HTTP_HEADER_ADD'])) $this->conf['send_HTTP_HEADER_ADD'] = array();
        $this->conf['send_HTTP_HEADER_ADD'][] = array($var, $val);  
    }
    
    private function addRootGroupReject($configVarName, $registrNamePrefix){
        if($configVarName == 'PostingConfigGlobalReject'){
            $allRejects = T3Db::api()->fetchCol("select groupName from buyers_channels_globalreject where idposting=?", $this->buyerChannel->id); 
        }
        else {
            $allRejects = T3Db::api()->fetchCol("select groupName from buyers_channels_onecompany where idposting=?", $this->buyerChannel->id);   
        }
         
        if(count($allRejects)){
            foreach($allRejects as $name){
                if(is_string($name) && strlen($name)){
                    $this->lead->setRegisterValue($registrNamePrefix . $name, array(
                        'buyerID' => $this->buyerChannel->id,
                        'datetime' => date("Y-m-d H:i:s"),
                    ));
                }        
            }
        }        
    }
    
    
    protected function addGlobalReject(){
        $this->addRootGroupReject('PostingConfigGlobalReject', '_GlobalReject_');
    }
    
    public function addOneCompanyReject(){
        $this->addRootGroupReject('PostingConfigOneCompany', '_OneCompany_');
    }
    
    
    /**
    * Получить URL сайта с которого был получен лид
    * URL дается не настоящий,  один из тех который объявлен в body этого лида
    */
    public function getSiteURL(){
        return $this->lead->getSiteURL_for_Buyer();    
    }
    
    /**
    * Если в Collect или Analytics файлах надо сделать дополнительную отправку данных баеру, 
    * то необходимо измерить затраченное на это время и передать его в систему,
    * что бы она вычла это время из T3 Runtime
    * 
    * @param mixed $runTime
    */
    protected function addPostToBuyerTime($runTime){
        if($runTime > 0){
            $this->result->additionalPostToBuyerRunTime+= (float)$runTime;
        }
    }

}