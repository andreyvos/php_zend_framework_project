<?php


class T3UserWebmasterAgents {
    protected static $_instance = null;
    protected $agents = array();
    
    
    static public function isAgent($id){
        return (bool)T3Db::api()->fetchOne('select count(*) from users_webmaster_agents where id=?', (int)$id);    
    }
    
    
    /**
    * Возвращает объект класса T3WebmasterCompanys
    * @return T3WebmasterCompanys
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
    * Загрузить объект агента вебмастера 
    * 
    * @param int $id
    * @return T3UserWebmasterAgent
    */
    public function getAgent_Not_Static($id){
        if(!isset($this->agents[$id])){
            $this->agents[$id] = new T3UserWebmasterAgent();
            $this->agents[$id]->fromDatabase($id);
        } 
        
        return $this->agents[$id];   
    }
    
    /**
    * (static) Загрузить объект агента вебмастера
    * 
    * @param int $id
    * @return T3UserWebmasterAgent
    */
    static public function getAgent($id){
        return self::getInstance()->getAgent_Not_Static($id);   
    }
    
    
    /**
    * Создание нового агента
    * 
    * @param mixed $login
    * @param mixed $password
    * @param mixed $nickname
    * @param mixed $email
    * @param mixed $agentOptions
    * 
    * @return T3UserWebmasterAgent
    */
    static function createNewAgent($login, $password, $nickname, $email, $agentOptions = array()){
        $user = T3Users::createT3Worker($login, $password, $nickname, $email, 'webmaster_agent');
        
        if($user){
            
            $agent = new T3UserWebmasterAgent();
            $agent->setParams($agentOptions); 
            
            $agent->id = $user->id;
            $agent->balance = 0;
            $agent->distributionRate = 0;
            $agent->contactEmail = $email;
            
            $agent->insertIntoDatabase();
            
            return $agent;
        }
        else {
            return false;
        } 
    }
    
    static public function getAgentsList($full = false){
        
        /** @var Zend_Db_Select */
        $select = T3Db::api()->select();
        
        $select
        ->from("users_webmaster_agents", false)
        ->joinInner("users", "users_webmaster_agents.id = users.id", array(
            'AgentID'       => 'id',
            'AgentNickname' => 'nickname',
        ))
        ->order("users.nickname");
        
        if(!$full){
            $select->where("users_webmaster_agents.distributionRate > 0");    
        }
        
        return T3System::getConnect()->fetchAll($select);    
    }
    
    static public function getRandomAgent($group = 'english'){
        return (int)T3System::getConnect()->fetchOne(
            "select id from users_webmaster_agents
                where distributionRate > 0 and `group` = ?
                order by (4*rand()*(select max(distributionRate) from users_webmaster_agents))+distributionRate desc",
            $group
        );
    }
    
    static public function updateBalance($agentID, $sum, $webmasterID = 0, $leadID = 0, $leadProduct = 'unknown', $version = 'v2'){
        if($sum != 0){
            if($version == 'v1' || $version == "1"){
                $version = "v1";    
            }
            else {
                $version = 'v2';    
            }
            
            $agentID        = (int)$agentID;
            $webmasterID    = (int)$webmasterID;
            $leadID         = (int)$leadID;
            
            $sum = round((float)$sum, 2);
            
            T3Db::api()->insert("webmaster_agents_billing", array(
                'agent_id'      => $agentID,
                'create_date'   => new Zend_Db_Expr("NOW()"),
                'sum'           => $sum,
                'webmasterID'   => $webmasterID,
                'leadID'        => $leadID,
                'leadProduct'   => $leadProduct,
                'version'       => $version,
            ));   
            
            T3Db::api()->update('users_webmaster_agents', array(
                'balance' => new Zend_Db_Expr("`balance`+{$sum}")
            ), "`id`='{$agentID}'"); 
        }      
    }
    
    static public function getNewAgentID($loginInV1){
        switch($loginInV1){
            case "DavidTonoyan":    $a = "1000036"; break; 
            case "CarynJ":          $a = "1000035"; break;
            case "Vlad":            $a = "1018365"; break;
            case "Ken":             $a = "1018787"; break;
            case "michaelm":        $a = "1018906"; break; 
            default:                $a = "0";    
        }
        
        return $a;    
    }
    
    
    static public function getOldAgentLogin($agentID){
        switch($agentID){
            case "1000036":    $a = "DavidTonoyan"; break; 
            case "1000035":    $a = "CarynJ"; break;
            case "1018365":    $a = "Vlad"; break;
            case "1018787":    $a = "Ken"; break;
            case "1018906":    $a = "michaelm"; break; 
            default:           $a = "0";    
        }
        
        return $a;    
    }
    
    static public function getUsers (){
    	return T3Db::api()->fetchAll("SELECT * FROM users WHERE activ='0' and ban='0' and TO_DAYS(registration_datetime) > TO_DAYS(NOW())-60 order by email_vf desc, id desc limit 1000");
    } 
    
    static public function approveUser($id){
    	$currentUser = T3Users::getInstance()->getCurrentUser();// gets full info about current user
		$data = array(
		    'email_vf'         => '1',
            'phone_vf'         => '1',
		    'activ'      	   => '1',
		    'activation_date'  => date("y-m-d G:i:s"), 
		    'activation_type'  => 'Manual ('.$currentUser->first_name.' '.$currentUser->last_name.')' // gets activation type to "manual (Agne Firs, Last Name)"
		);
		T3Db::api()->update('users', $data, 'id='.$id); // updates USERS table of t3api database with $data info WHERE id is $id passed to the function
		
		$user = new T3User();
		$user->fromDatabase($id);
		$user->getUserCompany()->status = 'activ'; 
		$user->getUserCompany()->saveToDatabase();
    }
    
    static public function getUsersTransfering(){
    	return T3Db::api()->fetchAll("SELECT * FROM users WHERE from_version1='1' AND (from_version1_status='verification' OR from_version1_status='verification_code') limit 1000");
    }
    
    static public function approveUserTransfering($id){
    	$currentUser = T3Users::getInstance()->getCurrentUser();// gets full info about current user
		$data = array(
		    'from_version1_status'  => 'complite',
		    'activation_date'       => date("y-m-d G:i:s"), 
		    'activation_type'       => 'Manual ('.$currentUser->first_name.' '.$currentUser->last_name.')' // gets activation type to "manual (Agne Firs, Last Name)"
		);
		
		T3Db::api()->update('users', $data, 'id='.$id); // updates USERS table of t3api database with $data info WHERE id is $id passed to the function
			
    }
}

