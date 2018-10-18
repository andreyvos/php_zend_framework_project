<?php
class T3BuyerPause {
    
    private $database;

    private static $_instance = null;

    public function __construct(){
        $this->database = T3Db::api();
    }
    
    public static function getInstance(){
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public static function getActiveTime($channel_id,$pausetime=null){
        if ($pausetime === null){
            $pausetime = date("Y-m-d H:i:s");    
        }
        
        $date = false;
        
        
        $BuyerChannel = new T3BuyerChannel();
        $BuyerChannel->fromDatabase($channel_id);
        
        $date = sprintf("%s 00:00:00",date("d-m-Y", time() + 84600 * 1));
        $date = TimeZoneTranslate::translate($BuyerChannel->timezone,'pst',$date);
        
        //$T3BuyerFilter = new T3BuyerFilter();  
        /*$T3BuyerFilter->fromDatabase($channel_id); 
        $date_condition = $T3BuyerFilter->getCondition('Date');
        $curdate = TimeZoneTranslate::translate('pst',$BuyerChannel->timezone,$pausetime);*/
        
        /*if ($date_condition->works){
            $today_mk = date("U",strtotime($curdate));
            $hour = (int)date("H",strtotime($curdate));
            
            for ($i=0;$i<5;$i++){
                if ($date == ''){
                    $timestamp = $today_mk+$i*86400;
                    $rule = T3BuyerFilter_Condition_Date::get_all_points($date_condition->misc,date("Y",$timestamp),date("m",$timestamp),date("d",$timestamp)); 
                    foreach ($rule as $item){
                        if ($i>0 || ($i==0 && $item['finish']['h']>$hour)){
                            if ($item['action'] == 'nopost'){
                                $date = sprintf("%04d-%02d-%02d %02d:%02d:00",date("Y",$timestamp),date("m",$timestamp),date("d",$timestamp),$item['finish']['h'],$item['finish']['m']);  
                                break;
                            }     
                        }
                    }
                }else{
                    break;
                }
            }
            $date = sprintf("%04d-%02d-%02d %02d:%02d:00",date("Y"),date("m"),date("d",$timestamp),$item['finish']['h'],$item['finish']['m']);
            $date = TimeZoneTranslate::translate($BuyerChannel->timezone,'pst',$date);   
        }*/ 
        
        return $date;        
    }
    
    public static function onlyPauseChannel($channel_id){
        try{
            self::getInstance()->database->query("UPDATE buyers_channels SET `status` = 'paused' WHERE id = ".$channel_id." LIMIT 1");
        }
        catch(Exception $e){}    
    }
    
    public static function pauseChannel($channel_id,$activedata=false){
        
        $is_paused = false;
        try{
            self::getInstance()->database->query("UPDATE buyers_channels SET `status` = 'paused' WHERE id = ".$channel_id." LIMIT 1");
            $is_paused = true;
        }
        catch(Exception $e){}
        
        if ($is_paused){
            if (!$activedata){
                try{
                    $activedata = self::getActiveTime($channel_id);
                }
                catch(Exception $e){}
            }
            
            if ($activedata){
                $data = array(
                    'date' => date("Y-m-d H:i:s"),
                    'activ_date' => $activedata,
                    'channel_id' => $channel_id,
                    'is_active'  => 0  
                );    
            }else{
                $data = array(
                    'date' => date("Y-m-d H:i:s"),
                    'channel_id' => $channel_id,
                    'is_active'  => 0 
                );    
            }
            
            try{
               self::getInstance()->database->insert("buyer_channels_postpause",$data); 
            }
            catch(Exception $e){}
        }
    }
    
    public static function activeChannel(){
        $channels = self::getInstance()->database->fetchAll("select * from buyer_channels_postpause where is_active=0 and activ_date is not null and activ_date < ?",date("Y-m-d H:m:i"));
        foreach ($channels as $item){
            $data = array(
                'is_active'  => 1
            );
            self::getInstance()->database->update('buyer_channels_postpause',$data,"id=".$item['id']);
            if ($item['channel_id']>0){
                self::getInstance()->database->query("UPDATE buyers_channels SET `status` = 'active' WHERE id = ".$item['channel_id']." LIMIT 1");
            }
        }    
    }
    
    
}   