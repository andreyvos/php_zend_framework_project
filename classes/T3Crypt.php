<?php

class T3Crypt {
    const SECRETKEY = '000e00000000e000054300000000e0';
    const SECRETIV = 'e00000e000e0000003084000008430';

    public static function Encrypt($str) {
        return base64_encode(
            @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, self::SECRETKEY, $str, MCRYPT_MODE_CFB, self::SECRETIV)
        );
    }

    public static function Decrypt($str) {
        return @mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128, self::SECRETKEY, base64_decode($str), MCRYPT_MODE_CFB, self::SECRETIV
        );
    }
}