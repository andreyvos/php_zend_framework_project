<?php

class T3Mail {
    
    /**
    * @param string $message
    * @param string $template
    * 
    * @return T3Mail_Message
    */
    static public function createMessage($message, array $params = array(), $test = false){
        $messageObj = new T3Mail_Message();
        
        if(is_array($params) && count($params))$messageObj->params = $params;
        
        $messageObj->loadMessage($message);
        $messageObj->renderMessage(!$test);
        if(!$test)$messageObj->insertIntoDatabase();
        
        return $messageObj;    
    }
    
    static public function getTemplatesMessages(){
        return T3System::getConnect()->fetchAll("select *,
        (select mail_unsubscribe_types.`name` from mail_unsubscribe_types where mail_unsubscribe_types.id=mail_templates_message.groupID) as groupName from mail_templates_message");    
    }
    
    static public function getTemplatesLayouts(){
        return T3System::getConnect()->fetchAll("select * from mail_templates_global");    
    }
    
    
    
}