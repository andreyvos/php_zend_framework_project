<?php

class T3Db {
    protected static $databases         = array();

    protected static $databaseSite      = null;
    protected static $databaseV1        = null;
    protected static $databaseSEO       = null;
    protected static $databaseARH       = null;
    protected static $database29        = null;
    protected static $databasefraud     = null;
    protected static $databasePostcode  = null;
    protected static $databaseCall      = null;
    protected static $databaseLogobaza  = null;

    static public function isV1Db(){
        return !is_null(self::$databaseV1);    
    }
    
    static public function getAllConection(){
        return array(
            'API'       => 'api',
            'API Slave' => 'apislave',
            'Site'      => 'site',
            'Cache'     => 'cache',
            'Logs'      => 'logs',
            'V1'        => 'v1',
            'logstemp'  => 'logstemp',
            // 'logstemp'  => 'logstemp',
        );    
    }
    
    /** @return Zend_Db_Adapter_Abstract */
    static public function api(){
        return T3System::getConnect();
    }    
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function site(){
        if(defined('standAloneRun_started')){
            if(!self::$databaseSite){
                self::$databaseSite = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('site'));
                
                self::$databaseSite->setFetchMode(Zend_Db::FETCH_ASSOC);
                self::$databaseSite->query("SET NAMES UTF8"); 
                self::$databaseSite->query("SET SESSION time_zone = 'US/Pacific'");

            }
            return self::$databaseSite;    
        }
        else {
            if(!Zend_Registry::isRegistered('db')){
                $core = new Core();
                $core->initDatabase();   
            }
            return Zend_Registry::get('db');    
        }
    }
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function cache(){
        return T3System::getConnectCache();    
    }
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function seo(){
        if(!self::$databaseSEO){
            self::$databaseSEO = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('seo'));
            
            self::$databaseSEO->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databaseSEO->query("SET NAMES UTF8"); 
            self::$databaseSEO->query("SET SESSION time_zone = 'US/Pacific'");

        }
        return self::$databaseSEO;   
    }
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function v1(){
        if(is_null(self::$databaseV1)){
            self::$databaseV1 = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('v1'));
            
            self::$databaseV1->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databaseV1->query("SET NAMES UTF8"); 
            self::$databaseV1->query("SET SESSION time_zone = 'US/Pacific'");
        }
        
        return self::$databaseV1;   
    }

    /** @return Zend_Db_Adapter_Abstract */
    static public function fraud(){
        if(is_null(self::$databasefraud)){
            self::$databasefraud = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('fraud'));

            self::$databasefraud->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databasefraud->query("SET NAMES UTF8");
            self::$databasefraud->query("SET SESSION time_zone = 'US/Pacific'");
        }

        return self::$databasefraud;
    }
    
    /** @return Zend_Db_Adapter_Abstract */    
    static public function ukpostcode(){
        $name = 'postcode';
        
        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));
            
            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8"); 
            //self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
             
        }
        return self::$databases[$name];   
    }
    
    static public function callreport(){
        $name = 'callreport';
        
        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));
            
            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8"); 
            //self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
             
        }
        return self::$databases[$name];   
    }

    /** @return Zend_Db_Adapter_Abstract */
    static public function arh(){
        if(!self::$databaseARH){
            self::$databaseARH = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('arh'));

            self::$databaseARH->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databaseARH->query("SET NAMES UTF8");
            self::$databaseARH->query("SET SESSION time_zone = 'US/Pacific'");

        }
        return self::$databaseARH;
    }
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function server29(){
        if(!self::$database29){
            self::$database29 = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('server29'));
            
            self::$database29->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$database29->query("SET NAMES UTF8"); 
            self::$database29->query("SET SESSION time_zone = 'US/Pacific'");

        }
        return self::$database29;   
    }
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function logs(){
        //return self::main('t3logs');
        
        $name = 'logs';
        
        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));
            
            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8"); 
            self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
        }
        return self::$databases[$name];   
    }

    static public function logstemp(){
        //return self::main('t3logs');

        $name = 'logstemp';

        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));

            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8");
            //self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
        }
        return self::$databases[$name];
    }

    /** @return Zend_Db_Adapter_Abstract */
    static public function logstemparch(){
        //return self::main('t3logs');

        $name = 'logstemparch';

        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));

            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8");
            //self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
        }
        return self::$databases[$name];
    }

    /**
     * База в которую сливаються логи для репортов, которые будут строиться на PG
     *
     * @return Zend_Db_Adapter_Abstract
     */
    static public function logobaza(){
        $name = 'logobaza';

        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));

            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8");
            //self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
        }
        return self::$databases[$name];
    }
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function test(){
        $name = 'test';
        
        if(!isset(self::$databases[$name])){                                       
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));
            
            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8"); 
            self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'"); 
            self::$databases[$name]->getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT); 

        }
        return self::$databases[$name];   
    }
    
    /** 
    * копия базы t3api с репликанта - 100% не основная базы (ТОЛЬКО ДЛЯ ЧТЕНИЯ!!!!)
    * 
    * @return Zend_Db_Adapter_Abstract 
    */ 
    static public function apiSlave(){
        $name = 'apislave';
        
        if(!isset(self::$databases[$name])){
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig($name));
            
            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8"); 
            self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
             
        }
        return self::$databases[$name];   
    }
    
    
    /** 
    * рандомная рабочая копия юазы t3api или если такой нет, то рабочая база (ТОЛЬКО ДЛЯ ЧТЕНИЯ!!!!)
    * пул серверов хранится в рабочей базе t3api.server_mysql_replicants_t3api
    * 1 раз в минуту происходит проверка
    * 
    * @return Zend_Db_Adapter_Abstract 
    */ 
    static public function apiReplicant(){
        $name = 'apireplicant';
        
        if(!isset(self::$databases[$name])){
            $conf = T3System::getDatabaseConfig('api');
            $servers = T3Db::api()->fetchAll("SELECT id, `server`, `priority` FROM `server_mysql_replicants_t3api` WHERE `status`=1 and priority>0");
            
            $replicantID = 0;
            if(count($servers)){
                $all = 0;
                foreach($servers as $el){
                    $all+= $el['priority'];
                } 
                
                $rand = rand(1, $all);
                
                $start = 1;
                foreach($servers as $el){
                    $fin = $start + $el['priority']; 
                    if($rand >= $start && $rand < $fin){
                        $replicantID = $el['id'];
                        $conf['host'] = $el['server']; 
                        break;
                    }
                    $start = $fin;
                }
                
            }
            else {
                self::$databases[$name] = T3Db::api();   
            }
            
            try {
                self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql($conf);
                self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
                self::$databases[$name]->query("SET NAMES UTF8"); 
                self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
            }
            catch(Exception $e){
                T3Db::api()->update("server_mysql_replicants_t3api", array(
                    'status'        => '0',
                    'last_reason'   => (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "?") .
                    ' | RUN | Invalid Connection:' . $e->getMessage(),
                ), "id='{$replicantID}'");
                
                self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql(T3System::getDatabaseConfig('api'));
                self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
                self::$databases[$name]->query("SET NAMES UTF8"); 
                self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");   
            }            
            /*
            [18:15:19] Alexey Truehighev: команда show slave status\G выдает всю инфу но нам оттуда нужно либо 3 строки - хотя я считаю достаточно одно
            [18:15:36] Alexey Truehighev:       Slave_IO_Running: Yes
                                                Slave_SQL_Running: Yes
                                                Seconds_Behind_Master: 0
            [18:15:47] Alexey Truehighev: вот Seconds_Behind_Master: 0 я считаю и нужно брать в расчет 
            */
        }
        return self::$databases[$name];   
    }
    
    /**
    * Обновление статустов репликантов
    * выставление t3api.server_mysql_replicants_t3api.status = 0 or 1 (работает в данный момент или нет)
    */
    static public function _updateReplicants_t3api(){
        $all = T3Db::api()->fetchAll("SELECT id, `server` FROM `server_mysql_replicants_t3api` WHERE `priority`>0");
        
        if(count($all)){
            foreach($all as $server){
                $conf = T3System::getDatabaseConfig('api');
                $conf['host'] = $server['server'];  
                
                $status = '0';
                $reason = '';
                
                try{                  
                    $db = new Zend_Db_Adapter_Pdo_Mysql($conf);
                    $db->setFetchMode(Zend_Db::FETCH_ASSOC);
                    $db->query("SET NAMES UTF8"); 
                    $db->query("SET SESSION time_zone = 'US/Pacific'"); 
                    
                    $res = $db->fetchAll("show slave status");           
                    
                    if(
                        isset($res[0]['Slave_IO_Running'], $res[0]['Slave_SQL_Running'], $res[0]['Seconds_Behind_Master']) &&
                        $res[0]['Slave_IO_Running'] == 'Yes' &&
                        $res[0]['Slave_SQL_Running'] == 'Yes' &&
                        $res[0]['Seconds_Behind_Master'] < 60
                    ){
                        $status = '1'; 
                        $reason = (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "?") .
                        ' | CHECK | auto on';
                    }
                    else {
                        $reason = (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "?") .
                            ' | CHECK | ' . var_export($res, 1);
                        }
                    }
                    catch(Exception $e){
                        // ошибки при подключении, сервер не доступен
                        $reason = (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "?") .
                        ' | CHECK | exaption: ' . $e->getMessage() . "\r\n" . var_export($conf, 1);
                }
                 
                T3Db::api()->update("server_mysql_replicants_t3api", array(
                    'status'        => $status,
                    'last_reason'   => $reason,
                ), "id='{$server['id']}'");
            }
        }    
    }
    
    
    static protected $mainDatabase;
    
    /** @return Zend_Db_Adapter_Abstract */ 
    static public function main($database = null){
        $name = 'main';
        
        if(!isset(self::$databases[$name])){
            $conf = T3System::getDatabaseConfig($name);
            
            self::$databases[$name] = new Zend_Db_Adapter_Pdo_Mysql($conf);
            
            self::$databases[$name]->setFetchMode(Zend_Db::FETCH_ASSOC);
            self::$databases[$name]->query("SET NAMES UTF8"); 
            self::$databases[$name]->query("SET SESSION time_zone = 'US/Pacific'");
            
            
            self::$mainDatabase = $conf['dbname'];
        }
        
        if(!is_null($database) && $database != self::$mainDatabase){
            self::$mainDatabase = $database;
            self::$databases[$name]->query("use `{$database}`");    
        }
        
        
        return self::$databases[$name];   
    }
}