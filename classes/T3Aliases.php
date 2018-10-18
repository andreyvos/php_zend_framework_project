<?php

class T3Aliases {
    // Количесво дней в течении котрых надо не использовать алиас что бы его можно было удалить
    const daysDeleteActiveAlias = 30;
    
    // Максимальное колличество алиасов, которое одновременно может использовать один вебмастер
    const maxAliasesToWebmaster = 20;
    
    /**
    * Функция для получения ID вебмастера из алиаса
    * 
    * @param mixed $IDorAlias
    * @return int $webmasterID
    */
    static public function getID($IDorAlias){
        if(is_numeric($IDorAlias)){
            if($IDorAlias == 3000){
                return 28806;   
            }   
            else if(strlen($IDorAlias) < 5){
                $IDorAlias = "v1:{$IDorAlias}";
            }
            else {
                return (int)$IDorAlias;
            }
        }
        
        // для версии 1 по feed url
        if(substr($IDorAlias, 0, 4) == 'v1f:'){
            $a = explode(":", $IDorAlias); 
            if(count($a) == 2){  
                $feed = trim($a[1]);
                $feed_wmlogin = T3Db::v1()->fetchOne("select userid from prfids where prfidurl rlike ?", $feed);
                if($feed_wmlogin != 'admin'){
                    $newID = T3Db::v1()->fetchOne("select t3v2ID from user where login=?", $feed_wmlogin);
                    if($newID > 0){
                        return $newID;    
                    }    
                }
                return 28806; // t3leads.admin    
            }
        }   
        
        // для версии 1 по webmasterID
        if(substr($IDorAlias, 0, 3) == 'v1:'){
            $a = explode(":", $IDorAlias);
            if(count($a) == 2){
                $v1ID = trim($a[1]);
                if(is_numeric($v1ID)){
                    if($v1ID != 1000){
                        $newID = T3Db::v1()->fetchOne("select t3v2ID from user where id=?", $v1ID);
                        if($newID > 0){
                            return $newID;    
                        }
                    }  
                    return 28806; // t3leads.admin       
                }
            }    
        }
        
        if(strlen($IDorAlias)){   
            $result = T3System::getConnect()->fetchRow("select webmaster,id from aliases where `name`=? and `status`='active'", $IDorAlias);
            
            if($result){
                T3System::getConnect()->update('aliases', array(
                    'clicks' => new Zend_Db_Expr('clicks+1'),
                    'last_click_date' => new Zend_Db_Expr('NOW()'),
                ), "id={$result['id']}");
                
                return $result['webmaster'];   
            } 
            return 0; 
        }  
        return null;  
    }
    
    static public function getAlias($name){
        return T3System::getConnect()->fetchRow("select * from aliases where name=? and status='active'", $name);
    }
    
    static public function getAliasById($id){
        return T3System::getConnect()->fetchRow("select *,UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_click_date) as seconds from aliases where id=?", $id);
    }
    
    static public function getAllAliases($webmaster){
        if(is_object($webmaster) && $webmaster instanceof T3WebmasterCompany){
            $webmaster = (int)$webmaster->id;    
        }
        else if(is_numeric($webmaster)){
            $webmaster = (int)$webmaster;    
        }
        else {
            return null;    
        }
        
        return T3Db::api()->fetchCol("select `name` from `aliases` where webmaster=? and `status`='active' order by `name`", $webmaster);    
    }
    
    static public function deleteByID($id){
        T3System::getConnect()->update('aliases', array(
            'status' => 'deleted', 
            'deleted_date' => new Zend_Db_Expr('NOW()')
        ), "id=" . (int)$id);    
    }
    
    static public function createAlias($name, $webmasterID){
        T3System::getConnect()->insert('aliases', array(
            'name'          =>  $name,
            'webmaster'     =>  (int)$webmasterID,
            'create_date'   =>  new Zend_Db_Expr("NOW()")
        ));
        
        return T3System::getConnect()->lastInsertId();
    } 
    
    static public function getCountAliasesForWebmaster($webmasterID){
        return T3System::getConnect()->fetchOne("select count(*) from aliases where webmaster=? and `status`='active'", $webmasterID);
    }  
}