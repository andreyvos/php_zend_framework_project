<?php

class T3Users_AgentManagers {
    /**
    * Агент, который видит всех
    */
    static public function isPubManager(){
        $agents = array(
            1059717
        );
        
        return in_array(T3Users::getInstance()->getCurrentUserId(), $agents);
    }
    
    /**
    * Агент который видит себя и своих учеников
    */
    static public function isWebmasterAgentManager(){
        return (
            T3Users::getCUser()->isRoleWebmasterAgent() && 
            count(T3UserWebmasterAgents::getAgent( T3Users::getCUser()->id )->getSlaveAgents())
        ) ? true : false;                                              
    }
    
    static public function isPubPriceManager(){                        
        $agents = array(
            1059717
        );
        
        return in_array(T3Users::getInstance()->getCurrentUserId(), $agents);
    }
    
    static public function isBuyerManager(){
        $agents = array(
            1059717
        );
        
        return in_array(T3Users::getInstance()->getCurrentUserId(), $agents);
    }
}