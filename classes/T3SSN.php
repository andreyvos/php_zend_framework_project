<?php
class T3SSN {
    static protected $data = array();
    static protected $ids_cache = array();

    static public function addCache($ssn, $mass){
        self::$data[$ssn] = $mass;
    }

    static public function load($ssn){
        $filter = new Zend_Filter_Digits();
        $ssn = $filter->filter($ssn);

        $crypt_ssn = T3Crypt::Encrypt((int)$ssn);
        $id = T3DB::api()->fetchOne("select id from `hash` where `hash`=?", array($crypt_ssn));

        if ($id > 0){
            $result = array(
                'id'    =>  $id,
                'hash'  =>  $crypt_ssn
            );
        }
        else {
            T3DB::api()->insert('hash',
                array('hash' => $crypt_ssn)
            );

            $id = T3DB::api()->lastInsertId();

            $result = array(
                'id'    =>  $id,
                'hash'  =>  $crypt_ssn
            );
        }

        self::$ids_cache[$id] = $ssn;

        if(isset($data[$ssn]))   self::$data[$ssn] = $data[$ssn];
        else                     self::$data[$ssn] = $result;
    }

    public static function encrypt($ssn){
        $filter = new Zend_Filter_Digits();
        $ssn = $filter->filter($ssn);

        if(!isset(self::$data[$ssn])) self::load($ssn);
        return self::$data[$ssn];
    }

    public static function decrypt($hash){
        $ssn = T3Crypt::Decrypt($hash);
        return $ssn;
    }

    public static function save($lead_id, $ssn){
        $encrypt_data = T3SSN::encrypt($ssn);
        $isset = T3DB::api()->fetchOne("select id from leads_data_ssn where id=?",array($lead_id));
        if (!$isset){
            T3DB::api()->insert('leads_data_ssn',array(
                'id'    => $lead_id,
                'ssn'   => $encrypt_data['id']
            ));
        }
    }

    /***********************************************************************/

    /**
     * Получить интовый крипт SSN-a
     *  из которого можно обратно получить SSN используя функцию T3SSN::getByID(ID)
     *
     * @param $ssn
     * @return int
     */
    static public function getID($ssn){
        $res = self::encrypt($ssn);
        return isset($res['id']) ? $res['id'] : 0;
    }

    /**
     * Получить SSN по его ID
     *
     * @param $id
     * @return bool|string - false or SSN length 9, only numeric
     */
    static public function getByID($id){
        $id = (int)$id;

        if(!isset(self::$ids_cache[$id])){
            self::$ids_cache[$id] = T3Db::api()->fetchOne('SELECT `hash` FROM `hash` WHERE id=?', $id);
            if(self::$ids_cache[$id] !== false){
                self::$ids_cache[$id] = T3Crypt::Decrypt(self::$ids_cache[$id]);
            }
        }

        return substr(sprintf('%09d', self::$ids_cache[$id]), 0, 9);
    }

}