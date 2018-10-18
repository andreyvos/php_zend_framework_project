<?php
class T3Entrance {

    //// ВНИМАНИЕ!!!!!!!!! при изменении этого параметра - его так же следует менять на гостевой части,при верификации телефона!!!!!!!!!!!!!!!!!!!!!!!!!
    const constSalt   = 'jwch77cm2327hyqwyiqw616nn4621';

    static protected function randomString($len){
        $h = '';
        for($i = 0; $i < $len; $i++){
            $h.= substr("abcdef0123456789", rand(0, 15), 1);
        }
        return $h;
    }


    static public function createHash($string, $stringIsMD5 = false){
        if(!$stringIsMD5) $string = md5($string);

        $salt = self::randomString(24);
        return sha1(self::constSalt . $string . $salt) . $salt;
    }

    static public function isValidHash($string, $hash){
        $salt = substr($hash, 40);
        return sha1(self::constSalt . md5($string) . $salt) . $salt == $hash;
    }
}