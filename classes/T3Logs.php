<?php

class T3Logs {

	public static $isRun = false;
	
	public static $out = array();
	
	public static $saveToDb = true;
	public static $script = true;
	
	public function __construct()
	{
	
	}
	
	public static function run( $script = null )
	{
	
		if(self::$isRun) return;
		
		ob_start();

		self::$script = $script;
		
		if(NULL === self::$script) self::$script = $_SERVER['SCRIPT_NAME'];
		
		$time_start = microtime(true);
		
		define('LOG_TIME_START_MICROTIME', $time_start);
		
		self::$isRun = true;
		
		return true;
	}

	
	
	public static function log()
	{
		$error = null;
		$ob_output = ob_get_contents();
		ob_end_clean();
        echo $ob_output;
		//if(preg_match("#^PHP (Fatal|Notice|Parse|Warning)$#i", $ob_output)) {
		

		if(preg_match("#PHP (Fatal|Notice|Parse|Warning|(.*))$#i", $ob_output)) {
			$error = $ob_output;
			//$error .= "\n cwd: ". getcwd(). "\n";
		}

		$time_end = microtime(true);
		define('LOG_TIME_END_MICROTIME', $time_end);
		
		$total_time  = LOG_TIME_END_MICROTIME - LOG_TIME_START_MICROTIME;
		
		$Site_longestTime  = 0;
		$Site_longestQuery = null;
		$Site_avg = 0;
		
		$API_longestTime  = 0;
		$API_longestQuery = null;
		$API_avg = 0;
		
		$CACHE_longestTime  = 0;
		$CACHE_longestQuery = null;
		$CACHE_avg = 0;
		/*
		if(is_array(Zend_Db_Table::getDefaultAdapter()->getProfiler()->getQueryProfiles()) && count(Zend_Db_Table::getDefaultAdapter()->getProfiler()->getQueryProfiles())){
			foreach (Zend_Db_Table::getDefaultAdapter()->getProfiler()->getQueryProfiles() as $query) {
				if ($query->getElapsedSecs() > $Site_longestTime) {
					$Site_longestTime  = $query->getElapsedSecs();
					$Site_longestQuery = $query->getQuery();
				}
			} 
		}
		
		if(Zend_Db_Table::getDefaultAdapter()->getProfiler()->getTotalNumQueries()){
                    $Site_avg = round(Zend_Db_Table::getDefaultAdapter()->getProfiler()->getTotalElapsedSecs() / Zend_Db_Table::getDefaultAdapter()->getProfiler()->getTotalNumQueries(), 3);  
		}
		*/
		if(is_array(T3Db::api()->getProfiler()->getQueryProfiles()) && count(T3Db::api()->getProfiler()->getQueryProfiles())){
			foreach (T3Db::api()->getProfiler()->getQueryProfiles() as $query) {
				if ($query->getElapsedSecs() > $API_longestTime) {
					$API_longestTime  = $query->getElapsedSecs();
					$API_longestQuery = $query->getQuery();
				}
			} 
		}

		if(is_array(T3Db::cache()->getProfiler()->getQueryProfiles()) && count(T3Db::cache()->getProfiler()->getQueryProfiles())){
			foreach (T3Db::cache()->getProfiler()->getQueryProfiles() as $query) {
				if ($query->getElapsedSecs() > $CACHE_longestTime) {
					$CACHE_longestTime  = $query->getElapsedSecs();
					$CACHE_longestQuery = $query->getQuery();
				}
			} 
		}
		
		
		if(T3Db::api()->getProfiler()->getTotalNumQueries()){
                    $API_avg = round(T3Db::api()->getProfiler()->getTotalElapsedSecs() / T3Db::api()->getProfiler()->getTotalNumQueries(), 3);  
		}

		if(T3Db::cache()->getProfiler()->getTotalNumQueries()){
                    $CACHE_avg = round(T3Db::cache()->getProfiler()->getTotalElapsedSecs() / T3Db::cache()->getProfiler()->getTotalNumQueries(), 3);  
		}
		
		if(!$error) {
		
			self::$out = array(
						'cron_script'				=> self::$script,
	                    'create_date'               => new Zend_Db_Expr("NOW()"),
	                    'run_time'                  => microtime(1) - LOG_TIME_START_MICROTIME,
	                    'memory_use'                => memory_get_usage(),
	                    'api_querys_count'          => T3Db::api()->getProfiler()->getTotalNumQueries(),        
	                    'api_querys_seconds'        => T3Db::api()->getProfiler()->getTotalElapsedSecs(),       
	                    'api_query_avg_seconds'     => $API_avg,  
	                    'api_long_query'            => $API_longestTime,      
	                    'api_query'                 => $API_longestQuery,        
	                    'cache_querys_count'        => T3Db::cache()->getProfiler()->getTotalNumQueries(),        
	                    'cache_querys_seconds'      => T3Db::cache()->getProfiler()->getTotalElapsedSecs(),        
	                    'cache_query_avg_seconds'   => $CACHE_avg,
	                    'cache_long_query'          => $CACHE_longestTime,       
	                    'cache_query'               => $CACHE_longestQuery,
	                    //'site_querys_count'          => Zend_Registry::get('db')->getProfiler()->getTotalNumQueries(),        
	                    //'site_querys_seconds'        => Zend_Db_Table::getDefaultAdapter()->getProfiler()->getTotalElapsedSecs(),       
	                    //'site_query_avg_seconds'     => $Site_avg,  
	                    //'site_long_query'            => $Site_longestTime,      
	                    //'site_query'                 => $Site_longestQuery
			);
			//varDump2(self::$out);
			if( self::$saveToDb) {
				T3Db::cache()->insert("cron_runtime_log", self::$out);
			}
		
		
		} else {
			self::$out = array(
						'cron_script'				=> self::$script,
	                    'create_date'               => new Zend_Db_Expr("NOW()"),
	                    'run_time'                  => microtime(1) - LOG_TIME_START_MICROTIME,
	                    'memory_use'                => memory_get_usage(),
	                    'message'          			=> $error       

			);
			
			if( self::$saveToDb) {
				T3Db::cache()->insert("cron_runtime_errors", self::$out);
				
				T3Mail::createMessage('cron_log_errors', array (
				  'cron_script' => self::$out['cron_script'],
				  'create_date' => self::$out['create_date'],
				  'run_time' => self::$out['run_time'],
				  'memory_use' => self::$out['memory_use'],
				  'message' => $error,
				))->addToArray( array('error8@t3leads.com', 'error8@t3leads.com'))->SendMail();

			}
		}
		return $ob_output;
	}
	
	
	public static function getLog()
	{
		return self::$out;
	}
	
	public static function getError()
	{
		//flopen();
	}
	
	public static function logError()
	{
		
		$error = null;
		$ob_output = ob_get_contents();
		ob_end_clean();
		if(preg_match("#PHP (Fatal|Notice|Parse|Warning|(.*))$#i", $ob_output)) {
			$error = $ob_output;
			$error .= "\n cwd: ". getcwd(). "\n";
		}
		
			self::$out = array(
						'cron_script'				=> self::$script,
	                    'create_date'               => new Zend_Db_Expr("NOW()"),
	                    'run_time'                  => microtime(1) - LOG_TIME_START_MICROTIME,
	                    'memory_use'                => memory_get_usage(),
	                    'api_querys_count'          => T3Db::api()->getProfiler()->getTotalNumQueries(),        
	                    'api_querys_seconds'        => T3Db::api()->getProfiler()->getTotalElapsedSecs(),       
	                    'api_query_avg_seconds'     => $API_avg,  
	                    'api_long_query'            => $API_longestTime,      
	                    'api_query'                 => $error,        
	                    'cache_querys_count'        => T3Db::cache()->getProfiler()->getTotalNumQueries(),        
	                    'cache_querys_seconds'      => T3Db::cache()->getProfiler()->getTotalElapsedSecs(),        
	                    'cache_query_avg_seconds'   => $CACHE_avg,
	                    'cache_long_query'          => $CACHE_longestTime,       
	                    'cache_query'               => $error,
	                    //'site_querys_count'          => Zend_Registry::get('db')->getProfiler()->getTotalNumQueries(),        
	                    //'site_querys_seconds'        => Zend_Db_Table::getDefaultAdapter()->getProfiler()->getTotalElapsedSecs(),       
	                    //'site_query_avg_seconds'     => $Site_avg,  
	                    //'site_long_query'            => $Site_longestTime,      
	                    //'site_query'                 => $Site_longestQuery
			);
			
			if( self::$saveToDb) {
				T3Db::cache()->insert("cron_runtime_log", self::$out);
			}
			
			return $ob_output;
	}

    public static function addfraudlog($action_id,$item_id,$details=array()){

        $browser_info = array(
            'HTTP_USER_AGENT'=>ifset($_SERVER['HTTP_USER_AGENT']),
            'HTTP_REFERER'=>ifset($_SERVER['HTTP_REFERER']),
            'HTTP_ACCEPT_LANGUAGE'=>ifset($_SERVER['HTTP_ACCEPT_LANGUAGE']),
            'HTTP_ACCEPT_ENCODING'=>ifset($_SERVER['HTTP_ACCEPT_ENCODING']),
            'HTTP_ACCEPT_CHARSET'=>ifset($_SERVER['HTTP_ACCEPT_CHARSET']),
            'HTTP_ACCEPT'=>ifset($_SERVER['HTTP_ACCEPT']),
            'COOKIE'=>ifset($_COOKIE)
        );

        $data = array(
            'action_id'=>$action_id,
            'datetime'=>date("Y-m-d h:i:s"),
            'user_id'=>T3Users::getCUser()->id,
            'user_ip'=>myHttp::get_ip_num($_SERVER['REMOTE_ADDR']),
            'browser_info'=>serialize($browser_info),
            'item_id'=>$item_id,
            'details'=>serialize($details)
        );

        
        	T3Db::fraud()->insert('log',$data);
       
        
    }
	
}
