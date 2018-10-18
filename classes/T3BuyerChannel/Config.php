<?php

class T3BuyerChannel_Config{
    
    protected $id;
    
    public $status;
    public $err;
    
    public $PostingConfig;
    public $PostingConfigModules;
    public $PostingConfigData;
    public $PostingConfigGlobalReject;
    public $PostingConfigOneCompany;
    
    public $modulesArray;
    
    public $ConfigModuleClassesArray;
    public $ConfigModuleClass_Collect;
    public $ConfigModuleClass_Send;
    public $ConfigModuleClass_Analysis; 
      
    
    /**
    * Узнать текущий ID
    */
    function getID(){
        return $this->id;        
    }
    
    /**
    * Обнуление всез свойств класса
    */
    protected function clearObject(){
        // обнуление всех переменных входящих в класс
        foreach(get_object_vars($this) as $key => $val) $this->$key = null;      
    }

    
    public function getConfigName(){
        if($this->id){
            return T3SYSTEM_BUYERS_CHANNELS_CONFIGS . DS . "{$this->id}.php";
        }
        return null;
    }
    
    public function existsConfigFile(){
        return file_exists($this->getConfigName());
    }
    
    /**
    * Задание значения переменной
    * 
    * @param string $part
    * @param string $var
    * @param mixed $val
    */
    public function setValue($part,$var,$val){
        $this->PostingConfig[$part][$var] = $val;
        
        if(preg_match('/^[A-Za-z0-9]{1,64}$/i', $part)){
            $partVar = "PostingConfig{$part}";
            $this->$partVar[$var] = $val;    
        }     
    }
    
    /**
    * Задание значения области
    * 
    * @param string $part
    * @param mixed $array
    */
    public function setValues($part,$array){
        $this->PostingConfig[$part] = $array;
        if(preg_match('/^[A-Za-z0-9]{1,64}$/i', $part)){
            $partVar = "PostingConfig{$part}";
            $this->$partVar = $array;
        }     
    }
    
    /**
    * Задание значения переменной, из раздела Data
    * 
    * @param mixed $part
    * @param mixed $var
    * @param mixed $val
    */
    public function setValueData($var,$val){
        $this->setValue('Data', $var, $val);    
    }
    
    /**
    * Создание нового ко
    * 
    * @param mixed $id
    * @param mixed $collect
    * @param mixed $send
    * @param mixed $analysis
    */
    public function create($id, $collect, $send, $analysis){
        if(is_numeric($id) && $id>0){
            $this->id = $id;
            $this->PostingConfig = array('Modules' => array (
                'Collect'   => $collect,
                'Send'      => $send,
                'Analysis'  => $analysis,
            ));
            
            $this->save();
            
            $this->load($id);
        }    
    }
    
    public function reload(){
        if(isset($this->id)){
            $this->load($this->id);
        }    
    }
    
    /**
    * 1. Загрузка конфига по ID
    * 1.1 загрузка файла
    * 1.2 проверка структуры этого файла
    * 2. опциональная проверка его данных:
    * 2.1 проверка существования 3-х файлов
    * 2.2 проверка наличия в них необходимых классов и функций)
    * 
    * @param mixed $id
    */
    public function load($id, $checkAll = true){
        $this->clearObject();
        
        $this->id = $id;
        $this->status = true;
        
        /*
        $configFname = $this->getConfigName();

        if (!$this->existsConfigFile()) return $this->addError("Config file not found"."\r\n".$configFname);
        
        // Загружаем конфигурационный файл
        try{
            include ($configFname);
        }
        catch (Exception $e){
            // TODO: Это значит что конфигурационный файл содержит ошибки. Ошибки нужно логировать
            return $this->addError("Config ".$configFname." containts error: ".($e->__toString())); 
        }
        */
        
        //$settings = T3Db::api()->fetchOne("select settings from buyers_channels where id=?", $this->id);
        $settings = T3BuyerChannels::getChannel($this->id)->settings;
        
        if($settings === false){
            return $this->addError("Config Not Found In Database");     
        }
        else {
            $PostingConfig = unserialize($settings);
            
            
            if(!is_array($PostingConfig)){
                return $this->addError("Invalid Config Object");     
            }        
        }
        
        // Теперь после загрузки нам становятся доступны следующие данные:
        // $PostingConfigModules - здесь хранится информация о далее нужных нам модулях
        // $PostingConfigData - здесь конфигурационная информация
        if(
            isset($PostingConfig['Modules']["Collect"]) && 
            isset($PostingConfig['Modules']["Send"]) && 
            isset($PostingConfig['Modules']["Analysis"])
        ){
            $this->PostingConfig = $PostingConfig;
            $PostingConfig['Data'] = ifset($PostingConfig['Data'],array()); 
            $PostingConfig['GlobalReject'] = ifset($PostingConfig['GlobalReject'],array());
            $PostingConfig['OneCompany'] = ifset($PostingConfig['OneCompany'],array()); 
            
            $this->PostingConfigData =& $PostingConfig['Data'];
            $this->PostingConfigModules =& $PostingConfig['Modules']; 
            $this->PostingConfigGlobalReject =& $PostingConfig['GlobalReject'];
            $this->PostingConfigOneCompany =& $PostingConfig['OneCompany'];
            
            // если значение GlobalReject строка, то помещаем его в массив, и делаем первым элементом
            if(is_string($this->PostingConfigGlobalReject)) $this->PostingConfigGlobalReject = array($this->PostingConfigGlobalReject);  
            
            $this->modulesArray = array(
                    'Collect'   =>  T3SYSTEM_BUYERS_CHANNELS_COLLECT    . DS . $this->PostingConfigModules["Collect"]   .   '.php',
                    'Send'      =>  T3SYSTEM_BUYERS_CHANNELS_SEND       . DS . $this->PostingConfigModules["Send"]      .   '.php',
                    'Analysis'  =>  T3SYSTEM_BUYERS_CHANNELS_ANALYSIS   . DS . $this->PostingConfigModules["Analysis"]  .   '.php'
            );

            $allModulesExists = true;
            $errorLog = ""; // Лог ошибок связанных с не найденными модулями. Т.к. их может быть много

            foreach ($this->modulesArray as $modKey => $modulesArrayValue){
                $fname = $modulesArrayValue;
                if (file_exists($fname)){
                    // Тут тоже должен быть обработчик ошибок
                    include_once($fname);
                }
                else{
                    $errorLog.= "Config file name: " . $fname . "\r\n"."Config module name: " . $fname."\r\n";
                    $allModulesExists = false;
                }
            }

            if(!$allModulesExists) return $this->addError("Config file doesnt containts all modules: \r\n\r\n {$errorLog}");

            ////////////////////////////////////////////////////////////////////
            // В этом этапе мы создаем очень важный массив - ConfigModuleClassesArray
            ////////////////////////////////////////////////////////////////////
            
            // Этот массив содержит имена классов
            $ConfigModuleClassesArray = array();

            // Массив префиксов для различных типов классов
            $ConfigModuleFunctionPrefixesArray = array(
                "Collect"   =>  "PostingFiles_Collect_", 
                "Send"      =>  "PostingFiles_Send_", 
                "Analysis"  =>  "PostingFiles_Analysis_",
            );

            // Генерируем названия классов и проверяем их на существование
            $allFunctionsExists = true;
            $errorLog = ""; // Лог ошибок связанных с не найденными классами. Т.к. их может быть много

            foreach ($ConfigModuleFunctionPrefixesArray as $var => $classPrefix){
                // название кдасса
                $className = $classPrefix . $this->PostingConfigModules[$var];
                $ConfigModuleClassesArray[$var] = $className;
                
                
                // Здесь нужно устранить проблему связанную с что пытается произойти подгрузка класса при отсутствии
                if (class_exists($className)) { 
                    if (is_subclass_of($className, "T3PostingFile_".$var."Abstract")){
                        //echo "\r\nyes, ".$ConfigModuleClassesArray[$var]." = T3PostingFile_".$var."Abstract"."\r\n";
                    }
                    else {
                        $allFunctionsExists = false;
                        $errorLog.="Config file name: {$configFname} \r\n Config module type: {$var} \r\n {$className} not T3PostingFile_{$var}Abstract \r\n";
                    }
                    
                }
                else {
                    $allFunctionsExists = false;
                    $errorLog.= "Config File Name: " . $configFname . "\r\n" . "Config Module Type: " . $var . "\r\n" . "Class Name: " . $className."\r\n";   
                }
            }

            if (!$allFunctionsExists) return $this->addError("Config module does not containts classes"."\r\n {$errorLog}");

            $this->ConfigModuleClassesArray     = $ConfigModuleClassesArray;
            $this->ConfigModuleClass_Collect    = $ConfigModuleClassesArray['Collect'];
            $this->ConfigModuleClass_Send       = $ConfigModuleClassesArray['Send'];
            $this->ConfigModuleClass_Analysis   = $ConfigModuleClassesArray['Analysis'];
            
        }
        else {
            return $this->addError("Config file does not containts configuration arrays \r\n Config path: {$configFname}");
        }

        return true;
    }
    
    protected function addError($errorText,$status = false){
        $this->status = $status;
        $this->err = $errorText;
        
        return $this->status;         
    }
    
    /**
    * Может быть вызвана когда файл конфига уже загружен
    * 
    * 1 проверка существования 3-х файлов
    * 2 проверка наличия в них необходимых классов и функций)
    */
    public function checkAll(){
    
    }
    
    /**
    * Сохранение текущих данных в файл.
    * 
    * /------------------ Это не готово ----------------------/
    * /-------------/ Перед тем как данные запишутся они будут проверенны, и если есть какие то ошибки, файл не будет сохранен.
    * /-------------/ Также существует возможность сохранения файла с ошибками, при этом если есть ошибки 
    * /-------------/ статус канала, к которому принадлежит файл перейдет в error. ДОБАВИТЬ ТАКОЙ СТАТУС!
    */
    public function save($ignoreErrors = false){
        if($this->id){
            /*
            file_put_contents(
                $this->getConfigName(),
                "<?php \$PostingConfig = " . var_export($this->PostingConfig ,true) . ";"
            );
            */
            T3Db::api()->update('buyers_channels', array('settings' => serialize($this->PostingConfig)), "id={$this->id}");
        } 
    }
    
    /**
    * Получение массива структуры общего конфига (для 3-х файлов)
    */
    public function getStructure(){
        /**
        * Одной стороней функцией
        * ---
        * | Получить из конфига какие файлы (send, collect, аналайз) использует этот постинг канал
        * | Присоединить эти файлы
        * | Проверить их
        * ---
        * Создать объекты 3-х классов collect, send и analyze
        * Получитиь их этих объектоы требования к конфигу:
        * Class->getConfigValues()
        * Объединить 3 массива в один массив
        * Отдать этот масив
        */  
        
        if($this->status){
            $CollectClass   = $this->ConfigModuleClass_Collect; 
            $SendClass      = $this->ConfigModuleClass_Send; 
            $AnalysisClass  = $this->ConfigModuleClass_Analysis; 
            
            $CollectObject  = new $CollectClass();
            $SendObject     = new $SendClass();
            $AnalysisObject = new $AnalysisClass();
            
            
            $struct = array_merge(
                $CollectObject->getConfigValues(),
                $SendObject->getConfigValues(),
                $AnalysisObject->getConfigValues()
            );
            
            $temp_config = $this->PostingConfigData; 
            foreach($struct as $strKey => $strArr){
                $check_values = true;
                
                if(isset($strArr['hide']) && $strArr['hide'] === true){
                    $check_values = false;
                    unset($struct[$strKey]);   
                }
                
                if($check_values == true){
                    foreach($temp_config as $confKey => $confVal){
                        if($strKey == $confKey){
                            $struct[$strKey]['value'] = $confVal;
                            unset($temp_config[$confKey]);       
                        }
                    }
                }    
            }
                
            return $struct;
        }
        else {
            return array( );    
        }  
    }
    
    
    static public function getPostingFiles_Array($type = null){
        $return = array();
        
        if(is_null($type) || $type == 'collect'){
            $return['collect'] = glob(T3SYSTEM_BUYERS_CHANNELS_COLLECT . DS . "*.php");
            foreach($return['collect'] as &$filename){
                $filename = basename($filename,".php");        
            }
        }
        
        if(is_null($type) || $type == 'send'){
            $return['send'] = glob(T3SYSTEM_BUYERS_CHANNELS_SEND . DS . "*.php");
            foreach($return['send'] as &$filename){
                $filename = basename($filename,".php");        
            }
        }
        
        if(is_null($type) || $type == 'analysis'){
            $return['analysis'] = glob(T3SYSTEM_BUYERS_CHANNELS_ANALYSIS . DS . "*.php");
            foreach($return['analysis'] as &$filename){
                $filename = basename($filename,".php");        
            }
        }
        
        return $return; 
    } 
    
    static public function getPostingFilesCollect_Array(){
        return self::getPostingFiles_Array('collect');
    }
    
    static public function getPostingFilesSend_Array(){
        return self::getPostingFiles_Array('send');
    }
    
    static public function getPostingFilesAnalysis_Array(){
        return self::getPostingFiles_Array('analysis');
    }
    
       
}