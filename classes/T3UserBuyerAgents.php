<?php


class T3UserBuyerAgents  {
    
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
        $user = T3Users::createT3Worker($login, $password, $nickname, $email, 'buyer_agent');
        
        if($user){
            
            $agent = new T3UserBuyerAgent();
            $agent->setParams($agentOptions); 
            
            $agent->id = $user->id;
            $agent->contactEmail = $email;
            $agent->bonus_percent = 0;
            $agent->insertIntoDatabase();
            
            return $agent;
        }
        else {
            return false;
        } 
    }
    
    static public function getAgentsList($active_only = false){
        $where = "";
        if ($active_only) {
            $where = " WHERE users.activ = '1' AND users.ban = '0' ";
        }
        return T3Db::apiReplicant()->fetchAll("SELECT users.id as AgentID, users.nickname as AgentNickname FROM users_buyer_agents INNER JOIN users ON (users_buyer_agents.id = users.id) " . $where . " Order by users.nickname");
    }
    
    static public function getAgentsListPairs(){
        return T3Db::apiReplicant()->fetchPairs("SELECT users.id , users.nickname FROM users_buyer_agents INNER JOIN users ON (users_buyer_agents.id = users.id) Order by users.nickname");    
    }
    
    static public function getRandomAgent($group = 'english'){
        return (int)T3System::getConnect()->fetchOne(
            "select id from users_buyer_agents
                where distributionRate > 0
                order by (rand()*(select max(distributionRate) from users_webmaster_agents))+distributionRate"

        );
    }
    
    static public function getProducts($agentID = null, $type = 'main'){
        if($agentID == null)$agentID = T3Users::getCUser()->id;
        
        return T3Db::apiReplicant()->fetchCol('select product from users_buyer_agents_products where id=? and `type`=?', array($agentID, $type)); 
    }
    
    static public function getAgentsEmails($product, $notification = 'default'){
        
        $s = T3Db::api()->select()
        ->from("users_buyer_agents_products", null)
        ->joinInner("users", "users.id = users_buyer_agents_products.id", array(
            'email',
            'name' => 'nickname',
        ))
        ->where("users_buyer_agents_products.product=?", $product)
        ->where("users.role='buyer_agent'");
        
        if(T3Db::api()->fetchOne("select count(*) from users_buyer_agents_products_types where `name`=?", "disableNotif_{$notification}")){
            // Если для этого типа, предусмотренно отключение пользователей
            $s->where("users_buyer_agents_products.`type`=?", 'main');
            
            $disableAgents = T3Db::api()->fetchCol("select id from users_buyer_agents_products where product=? and `type`=?", array(
                $product, "disableNotif_{$notification}"     
            ));
            
            if(count($disableAgents)){
                $s->where("users_buyer_agents_products.id not in (" . implode(",", $disableAgents) . ")");       
            }
        }
        else {
            $s->where("users_buyer_agents_products.`type`=?", 'main');    
        }

        $agents = T3Db::api()->fetchAll($s);
        
        return $agents;
    }
}