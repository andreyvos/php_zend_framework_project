<?php

class T3Posting_Errors
{
    static public $_instatce = null;
    public $postingId    = null;
    public static  $_db = null;
    protected $db = null;
    
    public function __construct($config = null)
    {
        if(NULL !== $config && is_array($config) && count($config)) {
            foreach ($config as $k=>$v) $this->{$k} = $v;
        }
        
        if(NULL === $this->db) {
            $this->db = T3Db::api();
        }
    }
    
    public static function getInstance()
    {
        if(NULL == self::$_instatce) {
            self::$_instatce = new self();
        }
        
        return self::$_instatce;
    }
    
    /**
    * Добавление записи о таймауте
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $posting
    * @param string $description
    * @return int new id || false
    */
    static public function saveTimeout(T3Lead &$lead, T3BuyerChannel &$posting, $description){
        if(!$lead->is_test){
            self::save(2, $posting->id, $lead->id, $lead->data_email, "Timeout", $description); 
        }
    }
    
    /**
    * Добавление записи том что сервер недоступен
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $posting
    * @param string $description
    * @return int new id || false
    */
    static public function saveNoConnect(T3Lead &$lead, T3BuyerChannel &$posting, $description){
        if(!$lead->is_test){
            self::save(1, $posting->id, $lead->id, $lead->data_email, "No Connect", $description); 
        }   
    }
    
    /**
    * Добавление ошибки которую возвращает баер
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $posting
    * @param string $description
    * @return int new id || false
    */
    static public function saveBuyerError(T3Lead &$lead, T3BuyerChannel &$posting, $description){
        if(!$lead->is_test){
            self::save(4, $posting->id, $lead->id, $lead->data_email, "Buyer Error", $description); 
        }   
    }
    
    /**
    * Добавление записи о проблемах при парсинге ответа
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $posting
    * @param string $description
    * @return int new id || false
    */
    static public function saveParserResponseError(T3Lead &$lead, T3BuyerChannel &$posting, $description){
        if(!$lead->is_test){
            self::save(3, $posting->id, $lead->id, $lead->data_email, "Parser Response Error", $description); 
        }   
    }
    
    /** 
     * @param int $type [1-server down, 2-timeout, 3-invalid response]
     * @param int $posting_id
     * @param int $lead_id
     * @param string $lead_email
     * @param string $title
     * @param string $description
     * @return int new id || false	
	*/
    public static function save($type, $posting_id, $lead_id, $lead_email, $title, $description)
    {
        if(!isset($type, $posting_id, $lead_id, $lead_email, $title, $description)){
            return false;
        } 
        
        $data_error = array(
            'type'	            => (int)$type,
        	'date_create'		=> date("Y-m-d H:i:s"),
            'posting_id'		=> (int)$posting_id,
            'lead_id'	        => (int)$lead_id,
            'lead_email'		=> $lead_email,
            'title'	            => $title,
            'description'		=> $description,
        );    
        
        T3Db::api()->insert('posting_errors', $data_error);
        $lastInsertId = T3Db::api()->lastInsertId();
        if($lastInsertId) return $lastInsertId;
        else return false;
    }
    
    
    
    
    public function setPostingId($id)
    {
        $this->postingId = $id;
        return $this;
    }
    
    public function getPostingDataById($postingId)
    {
        return $this->db->fetchRow('SELECT bc.*, ucb.tech_emails FROM buyers_channels AS bc
        							LEFT JOIN users_company_buyer AS ucb ON ucb.id = bc.buyer_id
        							WHERE bc.id = '.(int)$postingId.' LIMIT 1');
    }
    
    
    public function getLatestPostingsErrorsGrouped( $type,  $last_date = null)
    {
        if( NULL === $last_date ) $last_date = new Zend_Db_Expr('NOW()');
        $q = "SELECT DISTINCT FROM posting_errors 
        	  WHERE type = ".$type." AND date_create >= ".$last_date." 
        	  GROUP BY posting_id
        	  ";
        return $this->db->fetchAll($q);
    }
    
    public function getLatestPostingErrorsByType($posting_id, $type, $limit = null)
    {
        if(NULL === $limit) $limit = 10;
        $limit = (int)$limit;
        $type = (int)$type;
        $posting_id = (int)$posting_id;
        
        $q = "SELECT * FROM posting_errors WHERE type = ".(int)$type." AND posting_id = ".$posting_id." ORDER BY id DESC LIMIT ".$limit." ";
        return $this->db->fetchAll($q);
    }
    
    public function getLatestErrors($type)
    {
        $type = (int)$type;
        $q = "SELECT * FROM posting_errors 
        	  WHERE `cron` = 0 AND `type` = ".(int)$type." ORDER BY id ASC";
        return $this->db->fetchAll($q);
    }
    
    public function getCronJobsRunDates()
    {
        $q = "SELECT type, date_run FROM posting_errors_cron ORDER BY `id` ASC";
        return $this->db->fetchPairs($q);
    }
    
    
    // server down functions
    public function getStatusDataByPostingIdAndType($posting_id, $type)
    {
         $q = "SELECT * FROM posting_errors_status WHERE posting_id = ".(int)$posting_id." AND error_type = ".(int)$type." LIMIT 1 ";
         return $this->db->fetchRow($q);   
    }
    
    public function insertStatusData($posting_id, $data)
    {
        $this->db->insert('posting_errors_status', $data);
        $lastInsertId = $this->db->lastInsertId();
        return (int)$lastInsertId;
    }
    
    public function updateStatusData($id , $data )
    {
        $this->db->update('posting_errors_status', $data, 'id = '.(int)$id);
    }
    
    public function updateStatusDataByPostingIdAndType($posting_ids , $type , $data)
    {
        $type = (int)$type;
        if(is_array($posting_ids) && count($posting_ids)) {
            $ids = implode(',', $posting_ids);
            $this->db->update('posting_errors_status', $data, 'posting_id IN ('.$ids.') AND error_type = '.$type. ' ');
        }
        return true;
    }
    
    public function getFromStatusTableForSendNoticeMail( $type, $seconds )
    {
        $type = (int)$type;
        $seconds = (int)$seconds;
        
        $q = "SELECT * FROM posting_errors_status WHERE error_type = ".$type." AND status = 1 AND   ( UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(date_create) ) >= ".$seconds." ";
        return $this->db->fetchAll($q);
        
    } 
    
    public function updatePostingErrorsStatus($ids, $status)
    {
        if(!is_array($ids)) $ids = array($ids);
        $data['status'] = (int)$status;
        if(count($ids)) {
            $ids = implode(',',$ids);
            $this->db->update('posting_errors_status', $data, ' id IN ('.$ids.') ');
        }
        return true;
    }
    
    public function updatePostingErrorsCronStatus($ids, $status)
    {
        if(!is_array($ids)) $ids = array($ids);
        $data['cron'] = (int)$status;
        if(count($ids)) {
            $ids = implode(',',$ids);
            $this->db->update('posting_errors', $data, ' id IN ('.$ids.') ');
        }
        return true;
    }
    
    public function getCountErrorsByStatusDateAndType($posting_id, $type, $date)
    {
        $type = (int)$type;
        $posting_id = (int)$posting_id;
               
        $q = "SELECT COUNT(id) FROM posting_errors WHERE type = ".$type." AND posting_id = ".$posting_id." AND date_create > '".$date."' ";
        return $this->db->fetchOne($q);
    }
    
    public function getPostingAutoOffStatus($posting_id)
    {
        $posting_id = (int)$posting_id;
        $q = "SELECT auto_on_off FROM buyers_channels WHERE id = ".$posting_id." ";
        return $this->db->fetchOne($q);
    }
    
    public function updatePostingAutoOffStatus($posting_id, $status)
    {
        $posting_id = (int)$posting_id;
        $status = (string)$status;
        $data['status'] = $status;
        $this->db->update('buyers_channels', $data, ' id = '.$posting_id.' ');
        return true;
    }
    
    public function getPostingErrorsStatusByTypeForOnPosting($type)
    {
        $type = (int)$type;
        
        $q = "SELECT * FROM posting_errors_status 
        		WHERE (  UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(date_create)  ) > 360 
        		AND  (  UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(date_create)  ) < 240  
        		AND error_type = ".$type."  AND `status` = 0 ";
        return $this->db->fetchAll($q); 
    }
    
    
    
}