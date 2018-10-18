<?php

class T3Monetization {
    static protected $monetizationSettings; 
    static protected $type;
    static protected $lead;
    
    
    static public function massAdd(){
        ini_set("memory_limit", "2000M");
        
        $minutes = 2;
        $dateMinutes = date('Y-m-d H:i', mktime(date('H'), date('i') - $minutes, 0, date('m'), date('d'), date('Y'))) ;  
        
        $leads = T3Db::api()->fetchCol("SELECT id FROM `leads_data` WHERE `datetime` BETWEEN '{$dateMinutes}:00' AND '{$dateMinutes}:59' and status in('sold','reject','pending')");
        
        if(count($leads)){
            foreach($leads as $id){
                $lead = new T3Lead();
                $lead->fromDatabase($id);
                
                self::add($lead, $lead->wm);   
            }
        }
    }
    
    static public function add(T3Lead $lead, $price){
        // Лид может монетизироваться только 1 раз
        if($lead->id && !T3Db::api()->fetchOne("select id from monetization_leads where lead=?", $lead->id)){
            self::$lead = $lead;  
            
            // Загурзка настроек монитизации
            self::$monetizationSettings = T3Db::api()->fetchRow(
                "select * from monetization_settings where product=? and channel_type=? and active=?",
                array(
                    T3Products::getID($lead->product),
                    $lead->get_method,
                    '1'
                )
            );

            // Если для этого лида есть настроеенная монитизация
            if(is_array(self::$monetizationSettings) && self::$monetizationSettings){
                self::$monetizationSettings['pingtree_details'] = Zend_Json::decode(self::$monetizationSettings['pingtree_details']);

                if($price > 0){
                    // лид продан
                    if($price >= self::$monetizationSettings['price']){
                        self::post('high_price'); 
                    }
                    else {        
                        self::post('low_price'); 
                    }
                }
                else {
                    // лид не продан, пятаемся монитизировать по 3-м схемам по порядку
                    if(!self::post('not_sold')){
                        if(!self::post('not_sold_dup')){
                            self::post('not_sold_time');
                        }
                    }
                }
            }
        }
    }
    
    static protected function post($type){
        self::$type = $type;

        if(isset(self::$monetizationSettings[$type])) {
            if(self::$monetizationSettings[$type]) {
                $status = T3Db::api()->fetchOne("select `status` from monetization_settings_details where `main`=? and `type`=? and webmaster=?", array(
                    self::$monetizationSettings['id'],
                    $type,
                    self::$lead->affid
                ));
                if(($status === false && self::$monetizationSettings[$type] == '1') || $status) {
                    // Вебмастр подходит
                    if($type == 'not_sold_dup') {
                        // Для схемы Дупликат, проверяем был ли такой лид от другого вебмастера или был ли такой прподанный лид
                        if(T3Db::api()->fetchOne("select id from leads_data where data_email=? and (affid!=? || wm > 0) order by id desc limit 1", array(
                            self::$lead->data_email,
                            self::$lead->affid
                        ))
                        ) {
                            return self::insert(self::$monetizationSettings['delayed_minutes']);
                        }
                    }
                    else if($type == 'not_sold_time') {
                        self::insert(self::$monetizationSettings['delayed_days'] * 1440);
                    }
                    else {
                        return self::insert(self::$monetizationSettings['delayed_minutes']);
                    }
                }
            }
        }
        else {
            varExport(self::$monetizationSettings);
        }
        
        return false;
    }
    
    
    static protected function insert($delayMinutes){
        try {
            $s = date('Y-m-d H:i:s', mktime(date('H'), date('i') + $delayMinutes, 0));

            $pingtree = self::$monetizationSettings['pingtree'];
            if(
                isset(self::$monetizationSettings['pingtree_details'][self::$type]) &&
                self::$monetizationSettings['pingtree_details'][self::$type] > 0
            ){
                $pingtree = self::$monetizationSettings['pingtree_details'][self::$type];
            }

            T3Db::api()->insert("monetization_leads", array(
                'lead'      => self::$lead->id,
                'pingtree'  => $pingtree,
                'type'      => self::$type,
                'webmaster' => self::$lead->affid,
                'product'   => self::$monetizationSettings['product'],
                'pingtree_second'  => self::$monetizationSettings['pingtree_second'],
                'start'     => $s 
            ));
            
            T3Db::api()->insert("monetization_sender_tasks", array(
                'lead'      => self::$lead->id,
                'pingtree'  => $pingtree,
                'pingtree_second'  => self::$monetizationSettings['pingtree_second'],
                'start'     => $s,
            ));   
        }
        catch(Exception $e){
            // дополнительная проверка на дупликат, если во время проверки первой дупликата еще не было
            return false;
        }
        
        return true;   
    }
    
    static public function sender(){
        /**
        * Запускается 1 раз в минуту
        * Берет ограниченное количесво лидов
        * Забивает очередь по секундам, забивая сначала первые секунды
        * И отправляет
        */
        
        $sendInSecond = 2; 
        
        $all = T3Db::api()->fetchAll(
            "select id, lead, pingtree, pingtree_second from monetization_sender_tasks where `start` <= ? limit " . ($sendInSecond * 58), 
            date('Y-m-d H:i:s', mktime(date('H'), date('i'), 0))
        );
        
        
        $count = count($all);
        
        if($count){                                
            $ids = array();
            foreach($all as $el){
                $ids[] = $el['id'];  
            }
            T3Db::api()->delete("monetization_sender_tasks", "id in ('" . implode("','", $ids) . "')");
                                                                      
            for($i = 0; $i < $count; $i++){
                exec("php " . dirname(__FILE__) . "/../scripts/cron_jobs/monetization/post.php {$all[$i]['lead']} {$all[$i]['pingtree']} {$all[$i]['pingtree_second']} > /dev/null &", $o); 
                
                if(($i + 1) % $sendInSecond == 0){     
                    sleep(1);
                }
            } 
            // выполнить скрипт в фоновом режиме: exec("php filename.php > /dev/null &"); *NIX ONLY
        }
        
    }
}