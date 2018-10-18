<?php

class T3Mail_Subscribes {
    const subscribeGroupID = '11';
    
    /**
    * Создание новой рассылки
    * @return T3Mail_Subscribe_Item
    */
    static public function createNewSubscribe(){
        $obj = new T3Mail_Subscribe_Item();
        $obj->create_date = date("Y-m-d H:i:s");
        $obj->status = 'new';
        $obj->insertIntoDatabase();
        return $obj;
    }
    
    
    static public function sendMails(){
        //echo "1";
        $subscribes = T3Db::api()->fetchAll("select id from mail_subscribe where `status`='process' and isSending='1'");
        
        $sendArray = array();
        
        if(count($subscribes)){
            foreach($subscribes as $subscribeID){
                $subscribe = new T3Mail_Subscribe_Item();
                $subscribe->fromDatabase($subscribeID);
                
                // переиндексирование количесва пользователей
                //$subscribe->reIndexUsersCount();
                
                if($subscribe->usersCount == $subscribe->sendCount){
                    $subscribe->status = 'complite';
                    $subscribe->isSending = '0';
                    $subscribe->saveToDatabase();    
                }
                else {
                    // бронирование записей
                    T3Db::api()->query("LOCK TABLES mail_subscribe_users WRITE");
                    
                    $users = T3Db::api()->fetchAll("select id,idUser from mail_subscribe_users where idSubscribe=? and isBrone='0' and isSend='0' limit {$subscribe->sendInMinutes}", $subscribe->id);
                    if(count($users)){
                        $IDs = array();
                        foreach($users as &$u){
                            $IDs[] =& $u['id'];        
                        }
                        
                        T3Db::api()->update('mail_subscribe_users', array(
                            'isBrone' => '1',
                            'broneStart' => new Zend_Db_Expr("NOW()"),
                        ), "id in ('" . implode("','", $IDs) . "')");
                    }
                    
                    T3Db::api()->query("UNLOCK TABLES");  
                    
                    if(count($users)){  
                        foreach($users as $user){
                            $sendArray[] = array(
                                'id'            => $user['id'],
                                'idUser'        => $user['idUser'],
                                'idSubscribe'   => $subscribe->id,
                                'idTemplate'    => $subscribe->template_id,
                            );    
                        }
                    }
                }
            }    
        }
        
        
        
        if(count($sendArray)){ 
            foreach($sendArray as $el){     
                
                T3Mail::createMessage($el['idTemplate'])->SendMail_For_User($el['idUser']);
                
                T3Db::api()->update('mail_subscribe_users', array(
                    'isBrone' => '0', 
                    'isSend'  => '1', 
                ), "id = {$el['id']}");
                
                T3Db::api()->update('mail_subscribe', array(
                    'sendCount' => new Zend_Db_Expr("sendCount+1"), 
                ), "id = {$el['idSubscribe']}");  
            }   
        }
    } 
    
    static public function freeOldBrone(){
        $subscribes = T3Db::api()->fetchAll("select id from mail_subscribe where `status`='process'");
        
        if(count($subscribes)){
            foreach($subscribes as $subscribeID){
                $subscribe = new T3Mail_Subscribe_Item();
                $subscribe->fromDatabase($subscribeID);
                
                // освобождение записей
                $subscribe->freeOldBrone();
            }    
        }
    }        
}