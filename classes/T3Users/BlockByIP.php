<?php

class T3Users_BlockByIP {
    const ADD_COMPLITE              = '1';
    const ADD_ERROR_DUPLICCATE      = '2';
    const ADD_ERROR_INVALID_IP      = '3';
    const ADD_ERROR_INAVLID_BYTES   = '4';
    
    /**
    * Добавление на блокировку нового IP (или целой подсети) 
    * 
    * @param string $ip  IP адрес который надо добавить на блокировку (Format: XXX.XXX.XXX.XXX  Ex: 124.3.12.42)
    * @param int $bytes - количесво первых байт IP адреса, которые дорабвляются на блокировку. Возможные значения 4, 3, 2. X.X.X.X(4) X.X.X.*(3) X.X.*.*(2)
    * @return int (1 Complite) (2 Duplicate) (3 Invalid IP) (4 Invalid Bytes)
    */
    static public function add($ip, $title = null, $bytes = 4){
        if(in_array($bytes, array(4, 3, 2))){
            $int = myHttp::get_ip_num($ip);
            if($int){
                if($bytes == 3) $int = (int)($int / 256);
                if($bytes == 2) $int = (int)($int / 65536); 
                
                try{
                    $title = trim($title);
                    if(!strlen($title))$title = 'Unknown'; 
                    
                    T3Db::api()->insert("users_ip_blocks_int{$bytes}", array(
                        'ip'            => $int,
                        'title'         => $title,
                        'create_date'   => date("Y-m-d H:i:s"),
                        'creator'       => T3Users::getInstance()->getCurrentUserId(),
                    ));
                    
                    return self::ADD_COMPLITE;
                }
                catch(Exception $e){
                    return self::ADD_ERROR_DUPLICCATE;  
                }  
            }
            else {
                return self::ADD_ERROR_INVALID_IP;   
            }               
        }
        else {
            return self::ADD_ERROR_INAVLID_BYTES;   
        }
    }
    
    static public function is($ip = null){
        $int4 = myHttp::get_ip_num($ip);
        if($int4){
            $int3 = (int)($int4 / 256);
            $int2 = (int)($int3 / 256);
            
            if(T3Db::api()->fetchOne("select ip from users_ip_blocks_int4 where ip={$int4} limit 1")) return true;
            if(T3Db::api()->fetchOne("select ip from users_ip_blocks_int3 where ip={$int3} limit 1")) return true;
            if(T3Db::api()->fetchOne("select ip from users_ip_blocks_int2 where ip={$int2} limit 1")) return true; 
        } 
        
        return false;  
    }    
}