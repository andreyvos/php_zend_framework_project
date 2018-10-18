<?php

require_once 'T3Users.php';

class T3System {

    protected static $_instance = null;
    /**
    * @var T3Users
    */
    public $users;
    public $channels;
    
    /**
    * Ссылка на Объект базы данных
    * @var Zend_Db_Adapter_Abstract
    */
    protected $database;
    
    /**
    * Ссылка на Объект базы данных кешевых данных
    * @var Zend_Db_Adapter_Abstract
    */
    protected $databaseCache = null;
    
    static public function isDbCacheConnect(){
        return !is_null(self::getInstance()->databaseCache);    
    }

    protected function  initialize($session = true) {
        $this->initializeDatabase($session);
        
        if($session){
            $config = array(
                'name'           => 't3site.session_new', 
                'primary'        => 'id', 
                'modifiedColumn' => 'modified',
                'dataColumn'     => 'data',
                'lifetimeColumn' => 'lifetime' 
            ); 
            Zend_Db_Table::setDefaultAdapter(T3Db::site());
            Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($config));
            Zend_Session::rememberMe(172800); // 2 дня
            Zend_Session::start(); 
            Zend_Db_Table::setDefaultAdapter(T3Db::api());                     
        }
         
        
        $this->users = T3Users::getInstance($session);
        $this->channels = T3Channels::getInstance();
        
        Zend_Locale::setDefault('ru');
    }
    
    /**
    * Ссылка на Объект базы данных
    * @var Zend_Db_Adapter_Abstract
    */
    static public function getConnect($name = null){
        if($name == 'cache'){   
            return self::getInstance()->initializeDatabaseCache(); 
        }
        
        //return T3Db::main('t3api');
        return self::getInstance()->database;    
    }
    
    /**
    * Ссылка на Объект базы данных
    * @var Zend_Db_Adapter_Abstract
    */
    static public function getConnectCache(){
        return self::getConnect('cache');    
    }

    public function initializeDatabaseCache(){
        //return T3Db::main('t3cache');
        
        if(!$this->databaseCache){
            
            $this->databaseCache = new Zend_Db_Adapter_Pdo_Mysql(self::getDatabaseConfig('cache'));
            
            $this->databaseCache->setFetchMode(Zend_Db::FETCH_ASSOC);
            $this->databaseCache->query("SET NAMES UTF8"); 
            $this->databaseCache->query("SET SESSION time_zone = 'US/Pacific'");
            
        }
        
        return $this->databaseCache; 
        
    } 
    
    /**
    * Получить массив
    * 
    * @param mixed $databaseName
    * @return Zend_Config_Ini
    */
    static public function getDatabaseConfig($databaseName){
        $localFile = dirname(__FILE__) . "/../configs/local/databases.ini";
        $productionFile = dirname(__FILE__) . "/../configs/databases.ini"; 
        
        if(is_file($localFile)){
            try {
                $c = new Zend_Config_Ini($localFile, $databaseName);
            }
            catch(Exception $e){
                $c = new Zend_Config_Ini($productionFile, $databaseName);   
            }      
        }
        else {
            $c = new Zend_Config_Ini($productionFile, $databaseName);
        }
        
        return $c->toArray(); 
    }
    
    /**
    * Получить массив
    * 
    * @param mixed $databaseName
    * @return Zend_Config_Ini
    */
    static public function getConfig($name){
        $localFile = dirname(__FILE__) . "/../configs/local/{$name}.ini";
        $productionFile = dirname(__FILE__) . "/../configs/{$name}.ini"; 
        
        if(is_file($localFile)){
            try {
                $c = new Zend_Config_Ini($localFile);
            }
            catch(Exception $e){
                $c = new Zend_Config_Ini($productionFile);   
            }      
        }
        else {
            $c = new Zend_Config_Ini($productionFile);
        }
        
        return $c; 
    }
    
    protected function initializeDatabase($session){
        $conf = self::getDatabaseConfig('api');
        
        
        if(defined("NEW_LEAD_UNIQUE_T3API_USER") && NEW_LEAD_UNIQUE_T3API_USER == 1){
            if($conf['username'] == 't3api' && $conf['password'] == 'sqdzfl8s'){
                $conf['username'] = 't3api.lead';      
            }
            else if($conf['username'] == 't4api'){
                $conf['username'] = 't4api.lead';
            }
        }
        
        // для крон скриптов пользователя будут звать подругому
        if(defined("CRON_UNIQUE_T3API_USER") && CRON_UNIQUE_T3API_USER == 1){
            if($conf['username'] == 't3api' && $conf['password'] == 'sqdzfl8s'){
                $conf['username'] = 't3api.cron';      
            }
            else if($conf['username'] == 't4api'){
                $conf['username'] = 't4api.cron';
            }
        }  
        
        $this->database = new Zend_Db_Adapter_Pdo_Mysql($conf);
        $this->database->setFetchMode(Zend_Db::FETCH_ASSOC);  
        $this->database->query("SET NAMES UTF8");    
        $this->database->query("SET SESSION time_zone = 'US/Pacific'");   
        
        //$this->database = T3Db::main('t3api');
        
        Zend_Db_Table::setDefaultAdapter($this->database);  // убрать если нет сессии
        Zend_Db_Table_Abstract::setDefaultMetadataCache(
            Zend_Cache::factory( 'Core', 'File', 
                array(
                    'automatic_serialization' => true,
                    'logging' => 0,
                    'ignore_user_abort' => 0,
                    'lifetime' => 9999999
                ),
                array(
                    'cache_dir' => T3SYSTEM_CACHEDIR,
                    'file_name_prefix'    => 'zend_cache_t3s',
                )
            )
        );
    }

    /**
    * @return T3System Реестровый класс сисемы
    */
    public static function getInstance($session = true){
        if(is_null(self::$_instance)){
            self::$_instance = new self();
            self::$_instance->initialize($session);
        }
        return self::$_instance;
    }

    public function directQuery($queryFunction, $param1, $param2 = null, $param3 = null, $param4 = null){
        //использование этого метода не желательно /////////////////////////////////
        //trigger_error('directQuery to T3System', E_USER_WARNING);/////////////////
        ////////////////////////////////////////////////////////////////////////////
        switch($queryFunction){
          case 'insert' :
            $this->getConnect()->insert($param1, $param2);
            return $this->getConnect()->lastInsertId();
          case 'update' :
            return $this->getConnect()->update($param1, $param2, $param3);
          case 'delete' :
            return $this->getConnect()->delete($param1, $param2);
          default:
            return $this->getConnect()->$queryFunction($param1);
        }
    }

    protected function prependFileNameWithRootPath(&$fileName){
        if(strpos($fileName, T3SYSTEM_ROOT)===false){
            $fileName = T3SYSTEM_ROOT . DIRECTORY_SEPARATOR . $fileName;
        }
    }

    public function writeFile($fileName, $contents){
        $this->prependFileNameWithRootPath($fileName);
        //T3SYSTEM_DYNAMIC_FILES_PERMISSION
        //$this->keepWritePermission($fileName);
        file_put_contents($fileName, $contents);
    }

    public function deleteFile($fileName){
        $this->prependFileNameWithRootPath($fileName);
        //$this->keepFileWritePermission($fileName);
        unlink($fileName);
    }

    public function keepFileWritePermission($fileName){
        $this->prependFileNameWithRootPath($fileName);
        //throw new Exception('Not Implemented');
        /*$path = dir(T3SYSTEM_ROOT . DIRECTORY_SEPARATOR . $fileName);
        $file = basename(T3SYSTEM_ROOT . DIRECTORY_SEPARATOR . $fileName);
        if(!is_directory($path))
          
        mkdir($path, T3SYSTEM_DYNAMIC_FILES_PERMISSION);*/

        return true;

    }
  
    public function keepFolderWritePermission($path){
        throw new Exception('Not Implemented');
        //$this->keepWritePermission($path);
    }

    public function readFileContents($fileName){
        $this->prependFileNameWithRootPath($fileName);
        //throw new Exception('Not Implemented');
        return file_get_contents($fileName);
    }

    public function getCountries_Array(){
        $ar = $this->getConnect()->fetchAll("
            SELECT code_2, title_eng
            FROM geoip_country
        ");
        $result = array();
        foreach($ar as $v){
            $result[$v['code_2']] = $v['title_eng'];
        }
        return $result;
    }
    
    static public function setValue($name, $value){
        if(!self::getConnect()->fetchOne('select count(*) from system_registr where `name`=?', $name)){
            try{
                self::getConnect()->insert('system_registr', array(
                    'name' => $name,
                    'value' => serialize($value),
                ));
                
                return T3System::getInstance();   
            } 
            catch (Exception $e){}
        }
        
        self::getConnect()->update('system_registr', array(
            'value' => serialize($value),
        ), "`name`=" . self::getConnect()->quote($name)); 
        
        return T3System::getInstance();  
    }
    
    static public function getValue($name, $default = null){
        $value = self::getConnect()->fetchOne('select `value` from system_registr where `name`=?', $name);
        
        if($value !== false)    return unserialize($value);
        else                    return $default;    
    }

}