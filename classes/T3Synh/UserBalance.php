<?php

class T3Synh_UserBalance {
    protected static $error = null;
    
    static public function getError(){
        return self::$error;    
    }
    
    static protected function setError($message){
        self::$error = $message;    
    }
    
    static public function updateBalances($count = 100){
        $count = (int)$count;
        if($count < 0) $count = 0;
        //$users = T3Db::v1()->fetchCol("select id from user where stat='u' and t3v2ID='0' limit {$count}");
        
        T3Db::v1()->insert('session', array());
        $session = T3Db::v1()->lastInsertId();
        
        T3Db::v1()->query("update userchangebalance set `status`='brone', `session`='{$session}', `broneStart` = NOW() where `status`='nosend' limit {$count}");

        $array = T3Db::v1()->fetchAll("select id,userID,sum,createDate from userchangebalance where `session`='{$session}'");
        
        if(count($array)){
            foreach($array as $el){
                $save = false;
                
                T3Db::api()->beginTransaction();
                try {
                    $company = new T3WebmasterCompany();
                    $company->fromDatabase(array('T3LeadsVersion1_ID' => $el['userID']));
                    
                    if($company->id){
                        $balance = round((float)$el['sum'],2); 
                        
                        T3System::getConnect()->insert('webmasters_old_leads', array(
                            'webmaster_id'      =>  $company->id,
                            'action_sum'        =>  $balance,
                            'action_datetime'   =>  $el['createDate'],
                            'synh_session'      =>  $session,
                            'synh_id'           =>  $el['id'], 
                        ));
                        
                        /*
                        T3Report_Summary::addBalance(
                            $company->id,
                            $balance,
                            $el['createDate']
                        );
                        */
                        
                        $company->updateBalance($balance);
                        
                        $save = true;         
                    }
                    
                    T3Db::api()->commit(); 
                } 
                catch (Exception $e) {
                    T3Db::api()->rollBack(); 
                    $error = $e->getMessage();
                    $save = false;
                }
                
                try{
                    if($save){
                        T3Db::v1()->update('userchangebalance', array('status' => 'finish'), "id={$el['id']}");
                    }
                    else {
                        T3Db::v1()->update('userchangebalance', array(
                            'status'   => 'error',
                            'request'  => varExportSafe($el),
                            'response' => varExportSafe($error),
                        ), "id={$el['id']}");     
                    }
                }
                catch(Exception $e){
                    
                }
                
                 
            }   
        } 
    }
    
    
    static public function freeOldBrone(){
        $oldBrone = T3Db::v1()->fetchAll("select id,`session` from userchangebalance where `status`='brone' and UNIX_TIMESTAMP(broneStart) < UNIX_TIMESTAMP(NOW()) - 7200");
        
        if(count($oldBrone)){
            foreach($oldBrone as $el){
                if(T3Db::api()->fetchOne("select count(*) from webmasters_old_leads where synh_id=?", $el['id'])){
                    // Есть такая запись 
                    T3Db::v1()->update('userchangebalance', array('status' => 'finish'), "id={$el['id']}");   
                }
                else {
                    // Нет такой записи
                    T3Db::v1()->update('userchangebalance', array('status' => 'nosend'), "id={$el['id']}");    
                }
            } 
            
            echo count($oldBrone) . " Values";  
        }
        else {
            echo "Not Values";
        }        
    }
       
}