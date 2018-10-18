<?php


class T3Channel_Sign {
    /**
     * Получение уникального ключа для JS формы. Используеться для отслеживания времени между загрузкой формы и полученным лидом
     *
     * @param int $webmaster
     * @return string
     */
    static public function getKey($webmaster = 0){
        $key = AP_StringHash::createHashAlphaNumeric(32);
        T3Db::api()->insert("channels_sign_keys", array(
            "key"       => $key,
            "create"    => date('Y-m-d H:i:s'),
            "webmaster" => (int)T3Aliases::getID($webmaster),
            "ip"        => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "",
        ));

        return T3Db::api()->lastInsertId() . ".{$key}";
    }
}