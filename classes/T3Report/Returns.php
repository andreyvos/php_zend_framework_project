<?php

class T3Report_Returns {   
    private $system;
    private $database;
    
    function __construct(){
        $this->system = T3System::getInstance();
        $this->database = T3Db::apiReplicant(); 
    }
    
    
    public function getData( $params ){
        //varDump2($params);
        $page = (!isset($params['page']) || $params['page'] == '')? 1 : (int)$params['page'];
        $perPage = (!isset($params['perPage']) || $params['perPage'] == '') ? 50 : (int)$params['perPage'];
        $user_data = null;
        
        if(!isset($params['header_data']['dateFrom']) || $params['header_data']['dateFrom'] == '' ) $params['header_data']['dateFrom'] = date("Y-m-d", mktime()-24*60*60*7);
        if(!isset($params['header_data']['dateTill']) || $params['header_data']['dateTill'] == '') $params['header_data']['dateTill'] = date("Y-m-d", mktime());
        $dateFrom = $params['header_data']['dateFrom']; 
        $dateTill = $params['header_data']['dateTill'];
        
        $wmID = (isset($params['header_data']['webmasterID']) && $params['header_data']['webmasterID'] != '') ? (int)$params['header_data']['webmasterID'] : 0;
        $agentID = (isset($params['header_data']['agentID']) && $params['header_data']['agentID'] != '') ? (int)$params['header_data']['agentID'] : 0;
        $product = (isset($params['header_data']['product']) && $params['header_data']['product'] != '') ? $params['header_data']['product'] : false;
        $channelType = (isset($params['header_data']['channelType']) && $params['header_data']['channelType'] != '') ? $params['header_data']['channelType'] : false;     
        //varDump2($params);
        
        if(!isset($params['user_data'])) {
            $user_data = T3Users::getInstance()->getCurrentUser();  
        } else {
            $user_data = $params['user_data'];
        }
                
        $select = new Zend_Db_Select($this->database);
        $select->from('leads_returns');
        $select->where("`return_datetime` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTill} 23:59:59' ");
        
         if(count($params['header_data']['webmasters'])){
        
            if($params['header_data']['webmasterAction'] == 'include') $select->where("`affid` in (?)", $params['header_data']['webmasters'] );
            
                else $select->where("`affid` not in (?)", $params['header_data']['webmasters'] );
            
            }
            
            else {
            
            if($params['header_data']['agentWithout']) $select->where("`agentID`=?", '0');
            
            else if($params['header_data']['agentID']) $select->where("`agentID`=?", $params['header_data']['agentID']);
            
        } 
        
        if($user_data->role == 'admin') {
            if($product) $select->where("product = ? ", $product);
        if($wmID  && !count($params['header_data']['webmasters'])) { ( isset($params['header_data']['webmasterAction'])  &&  $params['header_data']['webmasterAction'] == 'include') ?  $select->where("affid = ?", $wmID) : $select->where("affid != ?", $wmID) ; }
            if($agentID) $select->where("agentID = ?", $agentID);
            if($channelType) $select->where("get_method = ?", $channelType);
            
        } elseif ($user_data->role == 'webmaster') {
            
            //$select->where("affid = ?", $wmID);
        if($wmID  && !count($params['header_data']['webmasters'])) { ( isset($params['header_data']['webmasterAction'])  &&  $params['header_data']['webmasterAction'] == 'include') ?  $select->where("affid = ?", $wmID) : $select->where("affid != ?", $wmID) ; }
            if($product) $select->where("product = ? ", $product);
            if($channelType) $select->where("get_method = ?", $channelType);
            
        } elseif ($user_data->role == 'webmaster_agent') {
            
            if($product) $select->where("product = ? ", $product);
            $select->where("agentID = ?", $agentID);
            //if($wmID) $select->where("affid = ?", $wmID);
            if($wmID  && !count($params['header_data']['webmasters'])) { ( isset($params['header_data']['webmasterAction']) &&  $params['header_data']['webmasterAction'] == 'include') ?  $select->where("affid = ?", $wmID) : $select->where("affid != ?", $wmID) ; }
            if($channelType) $select->where("get_method = ?", $channelType);  
                      
        } else {
            return array();
        }
        //varDump2($select->__toString());
        //$select->where("affid != ?", 0);
        $select->order('id DESC');
        $select->limitPage($page, $perPage);
//varDump2($select->__toString());
        return $this->database->fetchAll($select);
                
    }
    
    
    
}