<?php

class T3Mail_UnsubscribeGroups {
    static public function isPostGroup($user, $group){
        if(is_numeric($user)){
            $userID = $user;
            $user = new T3User();
            $user->fromDatabase($userID);    
        }
        
        if(!(is_object($user) && $user instanceof T3User && $user->id)){
            throw new Zend_Db_Exception('Invalid User Object');     
        }
        
        if(is_numeric($group)){
            $groupID = $group;
            $group = new T3Mail_UnsubscribeGroup();
            $group->fromDatabase($groupID);    
        }
        
        if(!(is_object($group) && $group instanceof T3Mail_UnsubscribeGroup && $group->id)){
            throw new Zend_Db_Exception('Invalid Group Object');     
        }
        
        if($group->isStatusNotActive()){
            // Если группа темплейта не активна, письмо посылается
            return true;    
        }
        
        $relation = T3Mail_UnsubscribeGroup_Relations::getRelation($user->id, $group->id);
        
        if(!$relation){ 
            // Уникальной настройки не предусмотренно
            if($group->isDefaultActionActive()){
                // нет уникальной настрйоки, группа по умолчанию активна
                return true;
            }
            else {
                // нет уникальной настрйоки, группа по умолчанию отключенна
                return false;
            }   
        }
        else {
            if($relation->isActionActive()){
                // уникальная настрйока, указывает на активность группы
                return true;
            }
            else {
                // уникальная настрйока, указывает на отключенную группу
                return false;
            }    
        }    
    }
    
    static public function isPost($user, $template){
        if(is_numeric($user)){
            $userID = $user;
            $user = new T3User();
            $user->fromDatabase($userID);    
        }
        
        if(!(is_object($user) && $user instanceof T3User && $user->id)){
            throw new Zend_Db_Exception('Invalid User Object');     
        }
        
        if(is_numeric($template)){
            $templateID = $template;
            $template = new T3Mail_Template_Message();
            $template->fromDatabase($templateID);    
        }
        
        if(!(is_object($template) && $template instanceof T3Mail_Template_Message && $template->id)){
            throw new Zend_Db_Exception('Invalid Template Object');     
        }
        
        $group = $template->getUnsubscribeGroup();
        
        if(!$group){
            // Если темплейт не принадлежит к группе, то по умолчанию он посылается
            return true;    
        }
    
        return self::isPostGroup($user, $group);
    }
    
    static public function getGroups_Array(){
        return T3System::getConnect()->fetchAll("select * from mail_unsubscribe_types order by title");
    }     
}