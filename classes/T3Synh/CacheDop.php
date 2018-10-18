<?php
/*
class T3Synh_CacheDop {
    static protected $webmasters = array();
    
    static protected function add_Abstract_V1($type, $idUserV1, $sum, $date = null){
        if(is_null($date))$date = date('Y-m-d');
        $idUserV1 = (int)$idUserV1;
        $sum = round((float)$sum, 2);
        $date = substr($date, 0 ,10);
        
        $fCount = "{$type}Count"; 
        $fSum = "{$type}Sum"; 
        
        $v2ID = T3Db::v1()->fetchOne("select t3v2ID from user where id=?", $idUserV1);  
        
        if($v2ID !== false){
            try{
                T3Db::v1()->insert("cache_dop", array(
                    'date'      => $date,
                    'iduser'    => $idUserV1,
                    $fCount     => 1,
                    $fSum       => $sum,
                ));
            }
            catch(Exception $e){
                T3Db::v1()->query("update LOW_PRIORITY cache_dop set `{$fCount}`=`{$fCount}`+1, `{$fSum}`=`{$fSum}`+{$sum} ".
                "where `date`=" . T3Db::v1()->quote($date) . " and `iduser`={$idUserV1}"); 
            } 
            
            if($v2ID > 0){
                try{
                T3Db::cache()->insert("cache_dop", array(
                        'date'      => $date,
                        'iduser'    => $v2ID,
                        $fCount     => 1,
                        $fSum       => $sum,
                    ));
                }
                catch(Exception $e){
                    T3Db::cache()->query("update LOW_PRIORITY cache_dop set `{$fCount}`=`{$fCount}`+1, `{$fSum}`=`{$fSum}`+{$sum} ".
                    "where `date`=" . T3Db::cache()->quote($date) . " and `iduser`={$v2ID}"); 
                }         
            }
        }    
    }
    
    static protected function add_Abstract_V2($type, $idUserV2, $sum, $date = null){
        if(is_null($date))$date = date('Y-m-d');
        $idUserV2 = (int)$idUserV2;
        $sum = round((float)$sum, 2);
        $date = substr($date, 0 ,10);
        
        $fCount = "{$type}Count"; 
        $fSum = "{$type}Sum"; 
        
        try{
        T3Db::cache()->insert("cache_dop", array(
                'date'      => $date,
                'iduser'    => $idUserV2,
                $fCount     => 1,
                $fSum       => $sum,
            ));
        }
        catch(Exception $e){
            T3Db::cache()->query("update LOW_PRIORITY cache_dop set `{$fCount}`=`{$fCount}`+1, `{$fSum}`=`{$fSum}`+{$sum} ".
            "where `date`=" . T3Db::cache()->quote($date) . " and `iduser`={$idUserV2}"); 
        }         
    }
    
    static public function addListManagement_V1($idUserV1, $sum, $date = null){
        self::add_Abstract_V1("listManagement", $idUserV1, $sum, $date);    
    }
    
    static public function addListManagement_V2($idUserV2, $sum, $date = null){
        self::add_Abstract_V2("listManagement", $idUserV2, $sum, $date);    
    }
       
}
*/