<?php

class T3Messages {
    static public $types = array(
        array(
            'type' => 'siteMessages',
            'title' => 'Site Messages',
            'link'  => '/account/contactus/',
            'img'   => '/img/48/net2.png',
        ),
        array(
            'type' => 'tickets',
            'title' => 'Tickets',
            'link'  => '/account/tickets/inbox',
            'img'   => '/img/48/users_and_companies.png', 
        ),
        array(
            'type' => 'feedbacks',
            'title' => 'Feedbacks',
            'link'  => '/en/account/report/feedbacks',
            'img'   => '/img/48/info.png', 
        ),
        array(
            'type' => 'careers',
            'title' => 'Careers',
            'link'  => '/en/account/careers/',
            'img'   => '/img/48/user.png', 
        ),
    );
    
    
    static public function getActiveAction(){
        // настройка
        if(isset($_GET['notification'])){
            if($_GET['notification'] == '1'){
                T3Users::getCUser()->setRegistrValue('messagesAdminNotification', '1');
            }
            else {
                T3Users::getCUser()->setRegistrValue('messagesAdminNotification', '0'); 
            }    
        }
        
        // подсчет количесва неотвеченных сообщений по категориям
        $result = array(
            //'tickets'       =>  T3Db::api()->fetchOne("select count(*) from tickets where root_id='0' and status='open'"),    
           // 'siteMessages'  =>  T3Db::site()->fetchOne("select count(*) from contactus where closed='0'"),  
            'feedbacks'     =>  T3Db::api()->fetchOne("select count(*) from feedbacks where `status`='0'"),  
        //    'careers'       =>  T3Db::site()->fetchOne("select count(*) from careers where closed='0'"),                      
        ); 
        
        // подсчет общего количества неотвеченных сообщений
        $notifications = 0;
        foreach($result as $count){
            $notifications+= $count;    
        }
        
        // управление уведомлениями
        AZend_Notifications::deleteNotificationToUser('messages', 0); 
        if($notifications){
            $users = T3Db::api()->fetchCol("select iduser from system_users_registr where `name`='messagesAdminNotification' and `value`='s:1:\"1\";'");
            if(count($users)){
                foreach($users as $userId){
                    AZend_Notifications::addNotificationToUser($userId, "{$notifications} Open Messages", "New {$notifications} Open Messages", "/account/index/messages/", "messages", 0);
                }
            }         
        }
        
        return $result;
    }    
}