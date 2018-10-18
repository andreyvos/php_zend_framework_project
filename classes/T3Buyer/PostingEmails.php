<?php

class T3Buyer_PostingEmails {
    
    public static $types = array(
         'general_tech_mails', 'timeout', 'server_down', 'invalid_response', 'errors', 'caps_notification',
    );
    
    public static function getDescription($type){
        return AZend_StaticContent::render("postEmails_{$type}");    
    }
    
    protected static $_instance = null;
    
    protected $system = null;
    
    protected $db = null;
    
    public function __construct()
    {
        $this->system = T3System::getInstance();
        $this->db = $this->system->getConnect();
    }
    
    /**
    * @return T3Buyer_PostingEmails Реестровый класс сисемы
    */
    public static function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function savePostingMails( $postingId, $type, $emails)
    {
        
        //varDump2(func_get_args());
        
        if(is_null($type) || $type == '') throw new Exception('Error Tech emails type');
        $postingId= (int)$postingId;
        if(!$postingId) throw new Exception('Error Tech emails postingId');
        
        $data = $this->getPostingDataById($postingId);
        
        //$emails_arr = explode(",",$emails);
        if(!is_array($emails)) $emails_arr = array($emails);
        else $emails_arr = $emails;
        
        
        if(count($emails_arr) && is_array($emails_arr)) {
            foreach ($emails_arr AS $mk=>$mv) {
                $emails_arr[$mk] = trim($mv);
            }
        } else throw new Exception('Error. Posting mails not set');

        //
        $mails = array();
        if(count($data) && is_array($data)) {
            if(is_null($data['tech_emails']) || $data['tech_emails'] == '') {
                $tmp_m = array();
                //if(!is_array($mails) || !count($mails)) {
                    foreach (self::$types AS $t) $tmp_m[$t] = array();
                //}
                $data['tech_emails'] = serialize($tmp_m);
            }
            //varDump2($data['tech_emails']);
            $mails = @unserialize($data['tech_emails']);
            //varDump2($mails);
            //$save_data = array($type=>$emails);
            if(isset($mails[$type])) {
                $mails[$type] =  $emails_arr;
            } else {
                $mails[$type] = $emails_arr;
            }
            //varDump2($mails);
            //if(!isset($_SESSION['count_save'])) $_SESSION['count_save'] = 1;
            //if(isset($_GET['count_save'])) $_SESSION['count_save'] = (int)$_GET['count_save'];
            //$_SESSION['count_save'] ++;
            
            //if($_SESSION['count_save'] == 5) varDump2($mails);
            
            $save_mails = serialize($mails);

            
            $where = Zend_Db_Table::getDefaultAdapter()->quoteInto('id = ?', (int)$postingId);
            $this->system->directQuery('update', 'buyers_channels', array('tech_emails' => $save_mails), $where);
            
            return true;
        }

        return false;
    }
    
    
    public function saveBuyerMails( $buyerId, $type, $emails)
    {
        //varDump2(func_get_args());
        if(is_null($type) || $type == '') throw new Exception('Error Tech emails type');
        $buyerId= (int)$buyerId;
        if(!$buyerId) throw new Exception('Error Tech emails $buyerId');
        
        $data = $this->getBuyerDataById($buyerId);
        
        //$emails_arr = explode(",",$emails);
        if(!is_array($emails)) $emails_arr = array($emails);
        else $emails_arr = $emails;
        
        
        if(count($emails_arr) && is_array($emails_arr)) {
            foreach ($emails_arr AS $mk=>$mv) {
                $emails_arr[$mk] = trim($mv);
            }
        } //else throw new Exception('Error. Posting mails not set');

        //
        $mails = array();
        if(count($data) && is_array($data)) {
            if(is_null($data['tech_emails']) || $data['tech_emails'] =='') {
                $tmp_m = array();
                //if(!is_array($mails) || !count($mails)) {
                    foreach (self::$types AS $t) $tmp_m[$t] = array();
                //}
                $data['tech_emails'] = serialize($tmp_m);
            }
            $mails = unserialize($data['tech_emails']);
            
            //$save_data = array($type=>$emails);
            if(isset($mails[$type])) {
                $mails[$type] =  $emails_arr;
            } else {
                $mails[$type] = $emails_arr;
            }
            
            $save_mails = serialize($mails);
            
            $where = Zend_Db_Table::getDefaultAdapter()->quoteInto('id = ?', (int)$buyerId);
            $this->db->update('users_company_buyer', array('tech_emails' => $save_mails), $where);
            
            return true;
        }

        return false;
    }
    
    
    public function getPostingMails( $postingId, $type)
    {
        $results = array();
        $data = $this->getPostingDataById($postingId);
        if(count($data) && is_array($data) && isset($data['tech_emails']) && $data['tech_emails'] != '') {
            $results = @unserialize($data['tech_emails']);
        }
        //varDump2($results);
        if( isset( $results[$type] ) && is_array($results[$type]) && (  trim($results[$type][0]) != '' ) ) {
            return $results[$type];
        }

        $results = $this->getBuyerMails( (int)$data['buyer_id'], $type); //varDump2($results);
        if(count($data) && is_array($data) && isset($data['tech_emails']) && $data['tech_emails'] != '') {
            $results = @unserialize($data['tech_emails']);
        }
        //varDump2($results);
        if( isset( $results[$type] ) && is_array($results[$type]) && (trim($results[$type][0]) != '') ) {
            return $results[$type];
        }

        return array();
    }
    
    public function getBuyerMails( $buyerId, $type)
    {
        $data = $this->getBuyerDataById($buyerId);
        $mails = array();
        if(count($data) && is_array($data)) {
            $mails = @unserialize($data['tech_emails']);
        }
        //varDump2($mails);
        if(!isset($mails[$type]) || !is_array($mails[$type])) {
            $mails[$type] = array();
        }
        
        return $mails[$type];
    }
	
    public function getBuyerIdByPostingId($postingId)
    {
    	return $this->getPostingDataById($postingId); 
    }
    
    protected function getPostingDataById($postingId)
    {
        return $this->db->fetchRow("SELECT * FROM buyers_channels WHERE id = ".(int)$postingId." ");        
    }
    
    protected function getBuyerDataById($buyerId)
    {
        return $this->db->fetchRow("SELECT * FROM users_company_buyer WHERE id = ".(int)$buyerId." ");
    }  
} 