<?php

class T3Synh_PostingDop {
    protected static $error = null;
    
    static public function getError(){
        return self::$error;    
    }
    
    static protected function setError($message){
        self::$error = $message;    
    }
    
    static public function update($count = 1){
        $users = T3Db::v1()->fetchAll("select idposting,idBuyerNewT3Leads,fromSyncDate,title,`types` from posting where isSync='0' and idBuyerNewT3Leads>0 limit {$count}");
        
        if(count($users)){
            foreach($users as $user){ 
                T3Db::v1()->update('posting', array(
                    'syncDateStart' => new Zend_Db_Expr("NOW()"), 
                    'syncStatus' => 'brone',
                ), "idposting={$user['idposting']}"); 
            }
            
            foreach($users as $user){
                self::updateOnePosting($user['idposting'], $user['idBuyerNewT3Leads'], $user['fromSyncDate'], $user['title'], $user['types']);    
            }
        }    
    }
    
    static public function updateOnePosting($oldID, $newID, $syncDate, $title = null, $type = null){ 
        $goodEnd = true;
        
        // удаление записей, которые старше даты синхронизации
        /**
        * 1. Получение списка
        * 3. Удаление его из новой системы
        * 2. Удаление его из старой системы
        */
        $array = T3Db::v1()->fetchAll("select id,`status`,`amount` from money_synh where v2ChannelID=? and UNIX_TIMESTAMP(createDate) < UNIX_TIMESTAMP(?)", array($newID, $syncDate));
        foreach($array as $el){
            if($el['status'] == 'brone'){
                $goodEnd = false;
            }
            else {
                if($el['status'] == 'ok'){
                    T3Db::api()->beginTransaction();
                    try {
                        if($el['amount'] > 0){  
                            $insertTable = 'buyers_leads_sellings';    
                        }
                        else {
                            $insertTable = 'buyers_leads_movements';    
                        }
                        
                        $newSell = T3Db::api()->fetchRow("select id,buyer_id,action_sum from {$insertTable} where syncId=? and (invoice_id='0' or invoice_id is NULL)", array($el['id']));
                        if($newSell){
                            T3Db::api()->update('users_company_buyer', array(
                                'balance' => new Zend_Db_Expr("balance+" . $newSell['action_sum'])    
                            ), 'id=' . $newSell['buyer_id']);  
                            
                            T3Db::api()->delete("buyers_leads_sellings", "id=" . $newSell['id']);       
                        } 
                        T3Db::api()->commit(); 
                    } 
                    catch (Exception $e) {
                        T3Db::api()->rollBack();
                        $goodEnd = false; 
                    }
                }
                
                T3Db::v1()->update('money', array('synh' => '0'), "synh={$el['id']}");
                T3Db::v1()->delete('money_synh', "id={$el['id']}");     
            }
        }
        
        
        // Создание записей в старой системе
        /**
        * 1. Получение всех значений
        * 2. Цикл
        * 3. Транзакция
        * 4. Создлание
        * 5. Апдейтинг
        */
        
        
        $array = T3Db::v1()->fetchAll(
            "select id,money2,idform,`datetime` from money where formtype=? and lender=? and UNIX_TIMESTAMP(`datetime`) > UNIX_TIMESTAMP(?) and synh='0' and `from`='my'",
            array(
                $type, 
                $title, 
                $syncDate
            )
        );
        foreach($array as $el){
            T3Db::api()->beginTransaction();
            try {
                T3Db::v1()->insert('money_synh', array(
                    'createDate'    =>  $el['datetime'], 
                    'leadID'        =>  $el['idform'],
                    'amount'        =>  round($el['money2'],2),
                    'v2ChannelID'   =>  $newID,
                ));
                
                $lastID = T3Db::v1()->lastInsertId();
                
                T3Db::v1()->update('money', array('synh' => T3Db::v1()->lastInsertId()), "id={$el['id']}"); 
                
                T3Db::api()->commit(); 
            } 
            catch (Exception $e) {
                T3Db::api()->rollBack();
                $goodEnd = false; 
            }
                
        }
        
        // Конец синхронизации
        if($goodEnd){
            T3Db::v1()->update('posting', array(
                'syncStatus' => 'free', 
                'isSync' => '1', 
            ), "idposting={$oldID}");    
        }
        else {
            T3Db::v1()->update('posting', array(
                'syncStatus' => 'free',
            ), "idposting={$oldID}");     
        }
    }
    
    
    static public function freeOldBrone(){
        $oldBrone = T3Db::v1()->query("update posting set syncStatus='free' where syncStatus='brone' and UNIX_TIMESTAMP(syncDateStart) < UNIX_TIMESTAMP(NOW())- 7200");       
    }
       
}