<?php

class T3Cache_CompanyUserContacts {
    static protected $data;
    
    static public function load($ids){
        $ids = array_unique($ids);
        
        foreach($ids as $key => $id){
            if(isset(self::$data[$id]))unset($ids[$key]);    
        }
        
        $ids = array_values($ids);
        
        $dataTemp = T3Db::apiReplicant()->fetchAll("select company_id, id, first_name, last_name, nickname, email, country, phones, icq, aim, skype, bestContactMethod from users where company_id in ('" . implode("', '", $ids) . "') group by company_id");
        $data = array();
        foreach($dataTemp as $key => $el){
            $el['phones'] = unserialize($el['phones']);
            
            $el['phonesStr'] = '';
            foreach($el['phones'] as $p){
                if(isset($p['phone'], $p['type']) && strlen($p['phone']) > 3){
                    if(strlen($el['phonesStr']))$el['phonesStr'].= ", ";
                    $el['phonesStr'].= "{$p['phone']} ({$p['type']})";    
                }    
            }
            if($el['phonesStr'] == '') $el['phonesStr'] = '-';
            
            $data[$el['company_id']] = $el;  
            unset($dataTemp[$key]);     
        }    
        
        foreach($ids as $id){
            if(isset($data[$id]))   self::$data[$id] = $data[$id];    
            else                    self::$data[$id] = false;        
        } 
    } 
    
    static public function getNickname($id, $link = true){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false){
            if(!$link)                          return self::$data[$id]['nickname'];
            else                                return "<a style='color:#007EBB' href='/en/account/users/view/id/" . self::$data[$id]['id'] . "'>" . self::$data[$id]['nickname'] . "</a>";
        }
        else {
            return '-';    
        }
    }
    
    static public function getEmail($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['email']; 
        else                            return '-';
    }
    
    static public function getFirstName($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['first_name']; 
        else                            return '-'; 
    }
    
    static public function getLastName($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['last_name']; 
        else                            return '-'; 
    }
    
    static public function getFullName($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['first_name'] . " " . self::$data[$id]['last_name']; 
        else                            return '-'; 
    }
    
    static public function getBestContactMethod($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['bestContactMethod']; 
        else                            return '-'; 
    }
    
    static public function getCountry($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['country']; 
        else                            return '-'; 
    }
    
    static public function getAIM($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['aim']; 
        else                            return '-'; 
    }
    
    static public function getPhones($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['phonesStr']; 
        else                            return '-'; 
    }
    
    static public function getICQ($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['icq']; 
        else                            return '-'; 
    }
    
    static public function getSkype($id){
        if(!isset(self::$data[$id])) self::load(array($id));
        
        if(self::$data[$id] !== false)  return self::$data[$id]['skype']; 
        else                            return '-'; 
    } 
}
