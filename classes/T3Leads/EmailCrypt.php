<?php

class T3Leads_EmailCrypt {
    static public function crypt($email){
        if(T3Users::getCUser()->isRoleWebmasterAgent()){
            return self::get($email);
        }
        return $email;
    }

    static public function get($email){
        if(strlen($email)){
            $a = explode("@", $email);
            if(count($a) == 2){
                $email = sprintf("%010u", crc32($a[0])) . "@" . $a[1];
            }
        }
        return $email;
    }
}