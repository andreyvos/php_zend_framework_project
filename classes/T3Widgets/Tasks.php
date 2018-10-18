<?php

class T3Widgets_Tasks extends T3Widgets_Abstract {
    public $info;
    public $notifArray;
    
    public function __construct(){

        $this->info = null;
        $this->notifArray = null;
        
        $this->show = false;    
        
        $notif = array(
            1000000 => array(1000002, 1000010, 1000013, 1000041, 1019081, 1019774),
        );
        
        if(isset($notif[T3Users::getCUser()->id]) && is_array($notif[T3Users::getCUser()->id]) && count($notif[T3Users::getCUser()->id])){
            $notifArray = T3Db::apiReplicant()->fetchAll("
                select id, nickname, (select count(*) from tasks where responsibleUser=users.id) as `count`
                from `users`
                where id in (" . implode(",", $notif[T3Users::getCUser()->id]) . ")
                order by `nickname`"
            );  

            if(count($notifArray)){
                $this->show = true;
                $this->notifArray = $notifArray;    
            }  
        } 
        
        if(T3Users::getCUser()->isRoleAdmin() || T3Users::getCUser()->isRoleBuyerAgent() || T3Users::getCUser()->isRoleWebmasterAgent()){
            $info = T3Db::apiReplicant()->fetchOne("select info from tasks_users_tasks_count where `user`=?", T3Users::getCUser()->id);
            
            if($info !== false) $info = unserialize($info);
             
            if(is_array($info) && count($info)){
                $this->show = true;
                $this->info = $info;   
            }
        }   
    }      
}